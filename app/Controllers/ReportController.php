<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Models\CommentModel;
use App\Models\NovelModel;
use App\Models\ReportModel;

final class ReportController extends Controller
{
    private const REASONS = ['spam', 'harassment', 'copyright', 'explicit', 'other'];

    public function store(Request $request): void
    {
        $userId = (int) Auth::id();
        if (RateLimiter::tooMany('report', (string) $userId, 10, 3600)) {
            $this->flashError('Terlalu banyak laporan dalam waktu singkat. Coba lagi nanti.');
            Response::back();
        }
        RateLimiter::hit('report', (string) $userId);

        $type = $request->string('target_type');
        $targetId = $request->int('target_id');
        $reason = $request->string('reason');
        $details = mb_substr($request->string('details'), 0, 1000);
        $fallback = '/';

        $novelId = null;
        $commentId = null;

        if ($type === 'novel') {
            $novel = NovelModel::find($targetId);
            if ($novel === null || $novel['publish_status'] !== 'published') {
                abort(404);
            }
            $novelId = (int) $novel['id'];
            $fallback = 'novel/' . $novel['slug'];
        } elseif ($type === 'comment') {
            $comment = CommentModel::find($targetId);
            if ($comment === null
                || $comment['status'] !== 'visible'
                || $comment['novel_publish_status'] !== 'published') {
                abort(404);
            }
            if ((int) $comment['user_id'] === $userId) {
                $this->flashError('Kamu tidak dapat melaporkan komentar milikmu sendiri.');
                Response::back('novel/' . $comment['novel_slug'] . '#komentar');
            }
            $commentId = (int) $comment['id'];
            $fallback = 'novel/' . $comment['novel_slug'] . '#komentar';
        } else {
            abort(404);
        }

        if (!in_array($reason, self::REASONS, true)) {
            $this->flashError('Alasan laporan tidak valid.');
            Response::back($fallback);
        }

        if (ReportModel::existsOpen($userId, $novelId, $commentId)) {
            $this->flashInfo('Kamu sudah mengirim laporan terbuka untuk konten ini.');
            Response::back($fallback);
        }

        ReportModel::create($userId, $novelId, $commentId, $reason, $details !== '' ? $details : null);
        $this->flashSuccess('Laporan diterima. Tim moderasi akan meninjaunya.');
        Response::back($fallback);
    }
}
