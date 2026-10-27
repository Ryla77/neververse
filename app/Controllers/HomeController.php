<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Models\CategoryModel;
use App\Models\ChapterModel;
use App\Models\NovelModel;
use App\Models\UserModel;

final class HomeController extends Controller
{
    public function index(Request $request): void
    {
        $this->render('home/index', [
            'title' => 'NovelVerse — Temukan cerita berikutnya',
            'metaDescription' => 'NovelVerse adalah platform membaca novel online berbahasa Indonesia. Jelajahi romansa, fantasi, reinkarnasi, kerajaan, petualangan, dan misteri.',
            'featured' => NovelModel::featured(),
            'popular' => NovelModel::collection('popular', 6),
            'latest' => NovelModel::collection('latest', 6),
            'updating' => NovelModel::collection('updated', 6),
            'recommended' => NovelModel::collection('rating', 3),
            'categories' => CategoryModel::allWithCounts(),
            'latestChapters' => ChapterModel::latestPublished(5),
            'stats' => [
                'novels' => NovelModel::countPublished(),
                'chapters' => ChapterModel::countPublished(),
                'authors' => UserModel::countByRole('author') + UserModel::countByRole('admin'),
                'readers' => UserModel::countByRole('reader'),
            ],
        ]);
    }
}
