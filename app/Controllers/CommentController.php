<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\CommentModel;
use App\Models\NovelModel;

final class CommentController extends Controller
{
    public function store(Request $request, string $slug): void
    {
        $novel = NovelModel::findPublicBySlug($slug);
        if ($novel === null) {
            abort(404);
        }

        $userId = (int) Auth::id();
        if (RateLimiter::tooMany('comment', (string) $userId, 10, 600)) {
            $this->flashError('Terlalu banyak komentar dalam waktu singkat. Coba lagi beberapa menit lagi.');
            Response::redirect('novel/' . $novel['slug'] . '#komentar');
        }
        RateLimiter::hit('comment', (string) $userId);

        $content = $request->string('content');
        $validator = (new Validator(['content' => $content]))
            ->required('content', 'Komentar')
            ->length('content', 'Komentar', 3, 1000);

        if ($validator->fails()) {
            $this->flashValidation($validator->errors(), ['content' => $content]);
            Response::redirect('novel/' . $novel['slug'] . '#komentar');
        }

        CommentModel::create((int) $novel['id'], $userId, $content);
        $this->flashSuccess('Komentar berhasil dikirim.');
        Response::redirect('novel/' . $novel['slug'] . '#komentar');
    }

    public function destroy(Request $request, string $id): void
    {
        $comment = CommentModel::find((int) $id);
        if ($comment === null) {
            abort(404);
        }

        $userId = (int) Auth::id();
        if ((int) $comment['user_id'] !== $userId && !Auth::isAdmin()) {
            abort(403);
        }

        CommentModel::delete((int) $comment['id']);
        $this->flashInfo('Komentar dihapus.');
        Response::back('novel/' . $comment['novel_slug'] . '#komentar');
    }
}
