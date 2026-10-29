<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Models\BookmarkModel;
use App\Models\CategoryModel;
use App\Models\ChapterModel;
use App\Models\CommentModel;
use App\Models\NovelModel;
use App\Models\RatingModel;
use App\Models\ReadingProgressModel;

final class NovelController extends Controller
{
    public function index(Request $request): void
    {
        $filters = [
            'q' => mb_substr($request->string('q'), 0, 100),
            'genre' => mb_substr($request->string('genre'), 0, 80),
            'sort' => $request->string('sort', 'latest'),
        ];
        if (!in_array($filters['sort'], ['latest', 'popular', 'rating', 'updated'], true)) {
            $filters['sort'] = 'latest';
        }

        $page = max(1, $request->int('page', 1));

        $activeGenre = $filters['genre'] !== '' ? CategoryModel::findBySlug($filters['genre']) : null;
        if ($filters['genre'] !== '' && $activeGenre === null) {
            $filters['genre'] = '';
        }

        $result = NovelModel::listPublic($filters, $page);

        $this->render('novels/index', [
            'title' => 'Jelajahi Novel — Luminara',
            'metaDescription' => 'Cari dan temukan novel original berdasarkan judul, penulis, dan genre favoritmu.',
            'result' => $result,
            'filters' => $filters,
            'activeGenre' => $activeGenre,
            'categories' => CategoryModel::allWithCounts(),
        ]);
    }

    public function show(Request $request, string $slug): void
    {
        $novel = NovelModel::findPublicBySlug($slug);
        if ($novel === null) {
            abort(404);
        }

        $viewKey = 'viewed_novel_' . (int) $novel['id'];
        if (!Session::get($viewKey)) {
            NovelModel::incrementViews((int) $novel['id']);
            $novel['views_count'] = (int) $novel['views_count'] + 1;
            Session::set($viewKey, true);
        }

        $chapters = ChapterModel::publishedByNovel((int) $novel['id']);
        $comments = CommentModel::visibleForNovel((int) $novel['id']);

        $userId = Auth::id();
        $bookmarked = $userId !== null && BookmarkModel::exists($userId, (int) $novel['id']);
        $userRating = $userId !== null ? RatingModel::userRating((int) $novel['id'], $userId) : null;

        $continueChapter = null;
        $progressPercent = null;
        $readIds = [];
        $lastChapterId = null;
        if ($userId !== null) {
            $readIds = ReadingProgressModel::readChapterIds($userId, (int) $novel['id']);
            $progress = ReadingProgressModel::find($userId, (int) $novel['id']);
            if ($progress !== null && $progress['chapter_id'] !== null) {
                $lastChapterId = (int) $progress['chapter_id'];
                $current = ChapterModel::find((int) $progress['chapter_id']);
                if ($current !== null
                    && (int) $current['novel_id'] === (int) $novel['id']
                    && $current['status'] === 'published') {
                    $continueChapter = $current;
                    $progressPercent = (float) $progress['progress_percent'];
                }
            }
        }

        $this->render('novels/show', [
            'title' => $novel['title'] . ' — Luminara',
            'metaDescription' => excerpt_text((string) $novel['synopsis'], 155),
            'novel' => $novel,
            'chapters' => $chapters,
            'comments' => $comments,
            'ratingSummary' => RatingModel::summary((int) $novel['id']),
            'userRating' => $userRating,
            'bookmarked' => $bookmarked,
            'continueChapter' => $continueChapter,
            'progressPercent' => $progressPercent,
            'readIds' => $readIds,
            'lastChapterId' => $lastChapterId,
            'related' => NovelModel::related((int) $novel['id'], 3),
        ]);
    }
}
