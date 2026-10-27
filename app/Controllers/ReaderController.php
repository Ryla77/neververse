<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\BookmarkModel;
use App\Models\ChapterModel;
use App\Models\NovelModel;
use App\Models\ReadingProgressModel;

final class ReaderController extends Controller
{
    public function show(Request $request, string $slug, string $chapter): void
    {
        $novel = NovelModel::findPublicBySlug($slug);
        if ($novel === null || !ctype_digit($chapter)) {
            abort(404);
        }

        $number = (int) $chapter;
        if ($number < 1) {
            abort(404);
        }

        $current = ChapterModel::findByNumber((int) $novel['id'], $number);
        if ($current === null || $current['status'] !== 'published') {
            abort(404);
        }

        $chapters = ChapterModel::publishedByNovel((int) $novel['id']);
        $prev = ChapterModel::previousNumber((int) $novel['id'], $number);
        $next = ChapterModel::nextNumber((int) $novel['id'], $number);

        $userId = Auth::id();
        $readIds = [];
        $bookmarked = false;
        if ($userId !== null) {
            $progress = ReadingProgressModel::find($userId, (int) $novel['id']);
            if ($progress === null || (int) $progress['chapter_id'] !== (int) $current['id']) {
                ReadingProgressModel::upsert($userId, (int) $novel['id'], (int) $current['id'], 0.0);
            }
            ReadingProgressModel::markChapterRead($userId, (int) $current['id']);
            $readIds = ReadingProgressModel::readChapterIds($userId, (int) $novel['id']);
            $bookmarked = BookmarkModel::exists($userId, (int) $novel['id']);
        }

        $words = count(array_filter(
            preg_split('/\s+/u', trim((string) $current['content'])) ?: [],
            static fn (string $part): bool => $part !== ''
        ));

        $this->render('reader/show', [
            'title' => $current['title'] . ' — ' . $novel['title'],
            'metaDescription' => 'Baca ' . $novel['title'] . ' bab ' . $number . ': ' . $current['title'] . ' di NovelVerse.',
            'novel' => $novel,
            'chapter' => $current,
            'chapters' => $chapters,
            'prev' => $prev,
            'next' => $next,
            'readIds' => $readIds,
            'bookmarked' => $bookmarked,
            'wordCount' => $words,
            'hideFooter' => true,
            'bodyClass' => 'reader-body',
        ]);
    }

    public function progress(Request $request, string $slug, string $chapter): void
    {
        $novel = NovelModel::findPublicBySlug($slug);
        if ($novel === null || !ctype_digit($chapter)) {
            abort(404);
        }

        $current = ChapterModel::findByNumber((int) $novel['id'], (int) $chapter);
        if ($current === null || $current['status'] !== 'published') {
            abort(404);
        }

        $rawPercent = $request->input('percent');
        $percent = is_scalar($rawPercent) ? (float) str_replace(',', '.', (string) $rawPercent) : -1.0;
        if ($percent < 0 || $percent > 100) {
            if ($request->wantsJson()) {
                Response::json(['ok' => false, 'message' => 'Progres membaca tidak valid.'], 422);
            }
            $this->flashError('Progres membaca tidak valid.');
            Response::redirect('novel/' . $slug . '/baca/' . (int) $chapter);
        }

        $userId = Auth::id();
        if ($userId !== null) {
            ReadingProgressModel::upsert($userId, (int) $novel['id'], (int) $current['id'], $percent);
            ReadingProgressModel::markChapterRead($userId, (int) $current['id']);
        }

        if ($request->wantsJson()) {
            Response::json([
                'ok' => true,
                'guest' => $userId === null,
                'percent' => round($percent, 2),
            ]);
        }

        Response::redirect('novel/' . $slug . '/baca/' . (int) $chapter);
    }
}
