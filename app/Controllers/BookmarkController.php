<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\BookmarkModel;
use App\Models\NovelModel;

final class BookmarkController extends Controller
{
    public function toggle(Request $request, string $slug): void
    {
        $novel = NovelModel::findPublicBySlug($slug);
        if ($novel === null) {
            abort(404);
        }

        $added = BookmarkModel::toggle((int) Auth::id(), (int) $novel['id']);
        $message = $added
            ? 'Novel ditambahkan ke perpustakaanmu.'
            : 'Novel dihapus dari perpustakaanmu.';

        if ($request->wantsJson()) {
            Response::json([
                'ok' => true,
                'bookmarked' => $added,
                'message' => $message,
                'count' => BookmarkModel::countForUser((int) Auth::id()),
            ]);
        }

        if ($added) {
            $this->flashSuccess($message);
        } else {
            $this->flashInfo($message);
        }
        Response::back('novel/' . $novel['slug']);
    }

    public function remove(Request $request, string $slug): void
    {
        $novel = NovelModel::findPublicBySlug($slug);
        if ($novel === null) {
            abort(404);
        }

        BookmarkModel::remove((int) Auth::id(), (int) $novel['id']);
        $this->flashInfo('Novel dihapus dari perpustakaanmu.');
        Response::redirect('perpustakaan');
    }
}
