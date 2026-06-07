<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\Organization\Article;
use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationMember;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Image;

class ArticleController extends Controller
{
    private function getOrgAndRole(Request $request, string $orgRoute): array
    {
        $org = Organization::where('route', $orgRoute)->first();
        if (! $org) {
            return [null, null];
        }

        $role = null;
        $user = $request->user('sanctum');
        if ($user) {
            $member = OrganizationMember::where('organization_id', $org->id)
                ->where('user_id', $user->id)
                ->first();
            $role = $member?->role;
        }

        return [$org, $role];
    }

    private function canPublish(?string $role): bool
    {
        return in_array($role, ['owner', 'admin', 'marketing']);
    }

    public function index(Request $request)
    {
        [$org, $role] = $this->getOrgAndRole($request, $request->route('orgRoute'));

        if (! $org) {
            return response()->json(['message' => 'org_not_found'], 404);
        }

        $query = Article::where('organization_id', $org->id)
            ->with('author:id,name,surname,username');

        if (! $this->canPublish($role)) {
            $query->whereNotNull('published_at')->where('published_at', '<=', now());
        }

        $articles = $query->orderBy('created_at', 'desc')->get()->map(fn ($a) => [
            'id' => $a->id,
            'title' => $a->title,
            'slug' => $a->slug,
            'excerpt' => $a->excerpt,
            'cover_image' => $a->cover_image,
            'published_at' => $a->published_at,
            'created_at' => $a->created_at,
            'author' => $a->author ? [
                'name' => $a->author->name.' '.$a->author->surname,
                'username' => $a->author->username,
            ] : null,
        ]);

        return response()->json(['message' => $articles], 200);
    }

    public function show(Request $request)
    {
        [$org, $role] = $this->getOrgAndRole($request, $request->route('orgRoute'));

        if (! $org) {
            return response()->json(['message' => 'org_not_found'], 404);
        }

        $query = Article::where('organization_id', $org->id)
            ->where('slug', $request->route('articleSlug'))
            ->with('author:id,name,surname,username');

        if (! $this->canPublish($role)) {
            $query->whereNotNull('published_at')->where('published_at', '<=', now());
        }

        $article = $query->first();

        if (! $article) {
            return response()->json(['message' => 'article_not_found'], 404);
        }

        return response()->json(['message' => [
            'id' => $article->id,
            'title' => $article->title,
            'slug' => $article->slug,
            'excerpt' => $article->excerpt,
            'content' => $article->content,
            'cover_image' => $article->cover_image,
            'published_at' => $article->published_at,
            'created_at' => $article->created_at,
            'org_name' => $org->name,
            'author' => $article->author ? [
                'name' => $article->author->name.' '.$article->author->surname,
                'username' => $article->author->username,
            ] : null,
        ]], 200);
    }

    public function store(Request $request)
    {
        [$org, $role] = $this->getOrgAndRole($request, $request->route('orgRoute'));

        if (! $org) {
            return response()->json(['message' => 'org_not_found'], 404);
        }
        if (! $this->canPublish($role)) {
            return response()->json(['message' => 'unauthorized'], 403);
        }

        $validator = Validator::make($request->only(['title', 'excerpt', 'content', 'publish']), [
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'publish' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->messages()->toArray());
        }

        $slug = $this->generateSlug($org->id, $request->title);

        $article = new Article([
            'organization_id' => $org->id,
            'author_id' => $request->user('sanctum')->id,
            'title' => $request->title,
            'slug' => $slug,
            'excerpt' => $request->excerpt,
            'content' => $request->content,
            'published_at' => $request->boolean('publish') ? now() : null,
        ]);

        if ($request->filled('cover_image')) {
            $article->cover_image = $this->saveCoverImage($request->cover_image, $org->route, $slug);
        }

        $article->save();

        if ($article->published_at) {
            NotificationService::sendToFollowers(
                $org->id,
                'notification.article_published',
                ['org' => $org->name, 'title' => $article->title],
                '/org/'.$org->route.'/news/'.$slug
            );
        }

        return response()->json(['message' => $article->id], 201);
    }

    public function update(Request $request)
    {
        [$org, $role] = $this->getOrgAndRole($request, $request->route('orgRoute'));

        if (! $org) {
            return response()->json(['message' => 'org_not_found'], 404);
        }
        if (! $this->canPublish($role)) {
            return response()->json(['message' => 'unauthorized'], 403);
        }

        $article = Article::where('id', $request->route('articleId'))
            ->where('organization_id', $org->id)
            ->first();

        if (! $article) {
            return response()->json(['message' => 'article_not_found'], 404);
        }

        $validator = Validator::make($request->only(['title', 'excerpt', 'content', 'publish']), [
            'title' => 'nullable|string|max:255',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'publish' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->messages()->toArray());
        }

        $updates = [];

        if ($request->filled('title') && $request->title !== $article->title) {
            $updates['title'] = $request->title;
            $updates['slug'] = $this->generateSlug($org->id, $request->title, $article->id);
        }
        if ($request->has('excerpt')) {
            $updates['excerpt'] = $request->excerpt;
        }
        if ($request->filled('content')) {
            $updates['content'] = $request->content;
        }

        if ($request->has('publish')) {
            if ($request->boolean('publish') && ! $article->published_at) {
                $updates['published_at'] = now();
            } elseif (! $request->boolean('publish')) {
                $updates['published_at'] = null;
            }
        }

        if ($request->filled('cover_image')) {
            $slug = $updates['slug'] ?? $article->slug;
            $updates['cover_image'] = $this->saveCoverImage($request->cover_image, $org->route, $slug);
        }

        if (! empty($updates)) {
            $article->update($updates);
        }

        return response()->json(['message' => 'Article updated'], 200);
    }

    public function destroy(Request $request)
    {
        [$org, $role] = $this->getOrgAndRole($request, $request->route('orgRoute'));

        if (! $org) {
            return response()->json(['message' => 'org_not_found'], 404);
        }
        if (! $this->canPublish($role)) {
            return response()->json(['message' => 'unauthorized'], 403);
        }

        $article = Article::where('id', $request->route('articleId'))
            ->where('organization_id', $org->id)
            ->first();

        if (! $article) {
            return response()->json(['message' => 'article_not_found'], 404);
        }

        if ($article->cover_image) {
            $path = storage_path('app/public/'.$article->cover_image);
            if (File::exists($path)) {
                File::delete($path);
            }
        }

        $article->delete();

        return response()->json(['message' => 'Article deleted'], 200);
    }

    public function uploadImage(Request $request)
    {
        [$org, $role] = $this->getOrgAndRole($request, $request->route('orgRoute'));

        if (! $org) {
            return response()->json(['message' => 'org_not_found'], 404);
        }
        if (! $this->canPublish($role)) {
            return response()->json(['message' => 'unauthorized'], 403);
        }

        $validator = Validator::make($request->only(['image']), [
            'image' => 'required|string',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->messages()->toArray());
        }

        $path = storage_path('app/public/org/'.$org->route.'/articles/images');
        if (! File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true, true);
        }

        $filename = Str::uuid().'.webp';

        Image::make($request->image)
            ->resize(1200, null, function ($c) {
                $c->aspectRatio();
                $c->upsize();
            })
            ->encode('webp', 85)
            ->save($path.'/'.$filename);

        $url = config('app.url').'/storage/org/'.$org->route.'/articles/images/'.$filename;

        return response()->json(['message' => $url], 200);
    }

    private function saveCoverImage(string $base64, string $orgRoute, string $slug): string
    {
        $path = storage_path('app/public/org/'.$orgRoute.'/articles');
        if (! File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true, true);
        }

        Image::make($base64)
            ->resize(1200, 630, function ($c) {
                $c->aspectRatio();
                $c->upsize();
            })
            ->encode('webp', 85)
            ->save($path.'/'.$slug.'-cover.webp');

        return 'org/'.$orgRoute.'/articles/'.$slug.'-cover.webp';
    }

    private function generateSlug(string $orgId, string $title, string $excludeId = null): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i = 1;

        while (true) {
            $query = Article::where('organization_id', $orgId)->where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
            if (! $query->exists()) {
                break;
            }
            $slug = $base.'-'.$i++;
        }

        return $slug ?: Str::uuid();
    }
}
