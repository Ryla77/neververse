<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Models\NovelModel;
use App\Models\RatingModel;

final class RatingController extends Controller
{
    public function store(Request $request, string $slug): void
    {
        $novel = NovelModel::findPublicBySlug($slug);
        if ($novel === null) {
            abort(404);
        }

        $userId = (int) Auth::id();
        if (RateLimiter::tooMany('rating', (string) $userId, 30, 3600)) {
            $this->flashError('Terlalu banyak perubahan rating. Coba lagi nanti.');
            Response::redirect('novel/' . $novel['slug'] . '#rating');
        }
        RateLimiter::hit('rating', (string) $userId);

        $rating = $request->int('rating', 0);
        if ($rating < 1 || $rating > 5) {
            $this->flashError('Pilih rating antara 1 sampai 5 bintang.');
            Response::redirect('novel/' . $novel['slug'] . '#rating');
        }

        RatingModel::upsert((int) $novel['id'], $userId, $rating);
        $this->flashSuccess('Rating kamu berhasil disimpan.');
        Response::redirect('novel/' . $novel['slug'] . '#rating');
    }
}
