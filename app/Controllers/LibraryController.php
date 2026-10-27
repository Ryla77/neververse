<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Models\BookmarkModel;
use App\Models\ReadingProgressModel;

final class LibraryController extends Controller
{
    public function index(Request $request): void
    {
        $userId = (int) Auth::id();

        $this->render('library/index', [
            'title' => 'Perpustakaan Saya — NovelVerse',
            'metaDescription' => 'Novel favorit, riwayat bacaan, dan progres membaca milikmu di NovelVerse.',
            'bookmarks' => BookmarkModel::listForUser($userId),
            'continueReading' => ReadingProgressModel::continueReading($userId),
            'history' => ReadingProgressModel::history($userId, 20),
            'historyTotal' => ReadingProgressModel::countHistory($userId),
        ]);
    }
}
