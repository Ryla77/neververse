<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Uploader;
use App\Core\Validator;
use App\Models\CategoryModel;
use App\Models\ChapterModel;
use App\Models\NovelModel;
use RuntimeException;
use Throwable;

final class AuthorController extends Controller
{
    public function dashboard(Request $request): void
    {
        $novels = NovelModel::byAuthor((int) Auth::id());

        $published = 0;
        $drafts = 0;
        $chapters = 0;
        $publishedChapters = 0;
        $views = 0;
        foreach ($novels as $novel) {
            if ($novel['publish_status'] === 'published') {
                $published++;
            } else {
                $drafts++;
            }
            $chapters += (int) $novel['chapter_total'];
            $publishedChapters += (int) $novel['chapter_count'];
            $views += (int) $novel['views_count'];
        }

        $this->render('author/dashboard', [
            'title' => 'Dashboard Penulis — Luminara',
            'metaDescription' => 'Kelola novel dan bab yang kamu tulis di Luminara.',
            'novels' => $novels,
            'summary' => [
                'novels' => count($novels),
                'published' => $published,
                'drafts' => $drafts,
                'chapters' => $chapters,
                'published_chapters' => $publishedChapters,
                'views' => $views,
            ],
        ]);
    }

    public function create(Request $request): void
    {
        $this->render('author/novel-form', [
            'title' => 'Novel Baru — Luminara',
            'metaDescription' => 'Buat novel baru sebagai draf dan lengkapi detailnya.',
            'novel' => null,
            'categories' => CategoryModel::all(),
            'selectedCategories' => [],
            'mode' => 'create',
        ]);
    }

    public function store(Request $request): void
    {
        $data = $this->novelData($request);
        $validator = $this->validateNovel($data);
        if ($validator->fails()) {
            $this->flashValidation($validator->errors(), $data);
            Response::redirect('tulis/novel/baru');
        }

        $coverPath = null;
        try {
            $coverPath = Uploader::image(
                $request->file('cover') ?? [],
                (string) config('uploads.covers_relative')
            );
        } catch (RuntimeException $e) {
            $this->flashValidation(['cover' => $e->getMessage()], $data);
            Response::redirect('tulis/novel/baru');
        }

        $title = $data['title'];
        try {
            $novelId = Database::transaction(function () use ($data, $title, $coverPath): int {
                $id = NovelModel::create([
                    'author_id' => (int) Auth::id(),
                    'title' => $title,
                    'slug' => NovelModel::uniqueSlug($title),
                    'synopsis' => $data['synopsis'],
                    'cover_path' => $coverPath,
                    'status' => $data['status'],
                    'publish_status' => 'draft',
                    'is_featured' => 0,
                ]);
                CategoryModel::syncForNovel($id, $data['categories']);
                return $id;
            });
        } catch (Throwable $e) {
            Uploader::delete($coverPath);
            throw $e;
        }

        $this->flashSuccess('Novel dibuat sebagai draf. Tambahkan bab, lalu publikasikan saat sudah siap.');
        Response::redirect('tulis/novel/' . $novelId . '/edit');
    }

    public function edit(Request $request, string $id): void
    {
        $novel = $this->ownedNovel((int) $id);

        $this->render('author/novel-form', [
            'title' => 'Edit ' . $novel['title'] . ' — Luminara',
            'metaDescription' => 'Edit detail novel, sampul, genre, dan status publikasi.',
            'novel' => $novel,
            'categories' => CategoryModel::all(),
            'selectedCategories' => CategoryModel::idsForNovel((int) $novel['id']),
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, string $id): void
    {
        $novel = $this->ownedNovel((int) $id);
        $data = $this->novelData($request);
        $validator = $this->validateNovel($data);
        if ($validator->fails()) {
            $this->flashValidation($validator->errors(), $data);
            Response::redirect('tulis/novel/' . $novel['id'] . '/edit');
        }

        Database::transaction(function () use ($novel, $data): void {
            NovelModel::update((int) $novel['id'], [
                'title' => $data['title'],
                'slug' => NovelModel::uniqueSlug($data['title'], (int) $novel['id']),
                'synopsis' => $data['synopsis'],
                'status' => $data['status'],
                'publish_status' => $novel['publish_status'],
            ]);
            CategoryModel::syncForNovel((int) $novel['id'], $data['categories']);
            NovelModel::touch((int) $novel['id']);
        });

        $this->flashSuccess('Detail novel berhasil diperbarui.');
        Response::redirect('tulis/novel/' . $novel['id'] . '/edit');
    }

    public function publish(Request $request, string $id): void
    {
        $novel = $this->ownedNovel((int) $id);
        $action = $request->string('action');

        if ($action === 'publish') {
            if (ChapterModel::countPublishedByNovel((int) $novel['id']) < 1) {
                $this->flashError('Publikasikan minimal satu bab sebelum menerbitkan novel.');
                Response::back('tulis/novel/' . $novel['id'] . '/edit');
            }
            NovelModel::setPublishStatus((int) $novel['id'], 'published');
            $this->flashSuccess('Novel berhasil dipublikasikan dan sekarang dapat dibaca.');
        } elseif ($action === 'unpublish') {
            NovelModel::setPublishStatus((int) $novel['id'], 'draft');
            $this->flashInfo('Novel dikembalikan menjadi draf dan disembunyikan dari pembaca.');
        } else {
            abort(404);
        }

        Response::back('tulis/novel/' . $novel['id'] . '/edit');
    }

    public function updateCover(Request $request, string $id): void
    {
        $novel = $this->ownedNovel((int) $id);

        try {
            $coverPath = Uploader::image(
                $request->file('cover') ?? [],
                (string) config('uploads.covers_relative')
            );
        } catch (RuntimeException $e) {
            $this->flashError($e->getMessage());
            Response::redirect('tulis/novel/' . $novel['id'] . '/edit');
        }

        if ($coverPath === null) {
            $this->flashError('Pilih berkas gambar sampul terlebih dahulu.');
            Response::redirect('tulis/novel/' . $novel['id'] . '/edit');
        }

        try {
            NovelModel::setCover((int) $novel['id'], $coverPath);
        } catch (Throwable $e) {
            Uploader::delete($coverPath);
            throw $e;
        }
        Uploader::delete($novel['cover_path']);
        $this->flashSuccess('Sampul novel berhasil diperbarui.');
        Response::redirect('tulis/novel/' . $novel['id'] . '/edit');
    }

    public function destroy(Request $request, string $id): void
    {
        $novel = $this->ownedNovel((int) $id);
        NovelModel::delete((int) $novel['id']);
        Uploader::delete($novel['cover_path']);
        $this->flashInfo('Novel beserta seluruh babnya telah dihapus.');
        Response::redirect('tulis');
    }

    public function chapters(Request $request, string $id): void
    {
        $novel = $this->ownedNovel((int) $id);

        $this->render('author/chapters', [
            'title' => 'Bab ' . $novel['title'] . ' — Luminara',
            'metaDescription' => 'Kelola daftar bab, draf, dan publikasi novel kamu.',
            'novel' => $novel,
            'chapters' => ChapterModel::allByNovel((int) $novel['id']),
            'publishedCount' => ChapterModel::countPublishedByNovel((int) $novel['id']),
        ]);
    }

    public function createChapter(Request $request, string $id): void
    {
        $novel = $this->ownedNovel((int) $id);

        $this->render('author/chapter-form', [
            'title' => 'Bab Baru — ' . $novel['title'],
            'metaDescription' => 'Tulis bab baru untuk novel kamu.',
            'novel' => $novel,
            'chapter' => null,
            'mode' => 'create',
        ]);
    }

    public function storeChapter(Request $request, string $id): void
    {
        $novel = $this->ownedNovel((int) $id);
        $data = $this->chapterData($request);
        $validator = $this->validateChapter($data);
        if ($validator->fails()) {
            $this->flashValidation($validator->errors(), $data);
            Response::redirect('tulis/novel/' . $novel['id'] . '/bab/baru');
        }

        $chapterId = ChapterModel::create((int) $novel['id'], $data['title'], $data['content'], $data['status']);
        NovelModel::touch((int) $novel['id']);
        $this->flashSuccess('Bab berhasil disimpan sebagai ' . ($data['status'] === 'published' ? 'publikasi' : 'draf') . '.');
        Response::redirect('tulis/novel/' . $novel['id'] . '/bab');
    }

    public function editChapter(Request $request, string $id): void
    {
        [$chapter, $novel] = $this->ownedChapter((int) $id);

        $this->render('author/chapter-form', [
            'title' => 'Edit Bab ' . $chapter['chapter_number'] . ' — ' . $novel['title'],
            'metaDescription' => 'Edit judul, isi, dan status bab.',
            'novel' => $novel,
            'chapter' => $chapter,
            'mode' => 'edit',
        ]);
    }

    public function updateChapter(Request $request, string $id): void
    {
        [$chapter, $novel] = $this->ownedChapter((int) $id);
        $data = $this->chapterData($request);
        $validator = $this->validateChapter($data);
        if ($validator->fails()) {
            $this->flashValidation($validator->errors(), $data);
            Response::redirect('tulis/bab/' . $chapter['id'] . '/edit');
        }

        ChapterModel::update((int) $chapter['id'], $data['title'], $data['content'], $data['status']);
        NovelModel::touch((int) $novel['id']);
        $this->flashSuccess('Bab berhasil diperbarui.');
        Response::redirect('tulis/novel/' . $novel['id'] . '/bab');
    }

    public function chapterStatus(Request $request, string $id): void
    {
        [$chapter, $novel] = $this->ownedChapter((int) $id);
        $action = $request->string('action');
        if (!in_array($action, ['publish', 'draft'], true)) {
            abort(404);
        }

        ChapterModel::setStatus((int) $chapter['id'], $action === 'publish' ? 'published' : 'draft');
        NovelModel::touch((int) $novel['id']);
        $this->flashSuccess($action === 'publish' ? 'Bab dipublikasikan.' : 'Bab disimpan sebagai draf.');
        Response::back('tulis/novel/' . $novel['id'] . '/bab');
    }

    public function destroyChapter(Request $request, string $id): void
    {
        [$chapter, $novel] = $this->ownedChapter((int) $id);
        ChapterModel::delete((int) $chapter['id']);
        NovelModel::touch((int) $novel['id']);
        $this->flashInfo('Bab dihapus.');
        Response::back('tulis/novel/' . $novel['id'] . '/bab');
    }

    private function ownedNovel(int $id): array
    {
        $novel = NovelModel::findOwned($id, (int) Auth::id());
        if ($novel === null) {
            abort(404);
        }
        return $novel;
    }

    /** @return array{0:array<string,mixed>,1:array<string,mixed>} */
    private function ownedChapter(int $id): array
    {
        $chapter = ChapterModel::find($id);
        if ($chapter === null) {
            abort(404);
        }
        $novel = NovelModel::findOwned((int) $chapter['novel_id'], (int) Auth::id());
        if ($novel === null) {
            abort(404);
        }
        return [$chapter, $novel];
    }

    private function novelData(Request $request): array
    {
        return [
            'title' => $request->string('title'),
            'synopsis' => $request->string('synopsis'),
            'status' => $request->string('status', 'ongoing'),
            'categories' => $this->categoryIds($request),
        ];
    }

    private function validateNovel(array $data): Validator
    {
        $validator = (new Validator($data))
            ->required('title', 'Judul')
            ->length('title', 'Judul', 3, 180)
            ->required('synopsis', 'Sinopsis')
            ->length('synopsis', 'Sinopsis', 30, 5000)
            ->required('status', 'Status novel')
            ->in('status', 'Status novel', ['ongoing', 'completed'])
            ->required('categories', 'Genre');

        return $validator;
    }

    private function categoryIds(Request $request): array
    {
        $raw = $request->input('categories');
        if (!is_array($raw)) {
            return [];
        }

        $available = array_map(static fn (array $category): int => (int) $category['id'], CategoryModel::all());
        $ids = [];
        foreach ($raw as $value) {
            if (!is_scalar($value) || !ctype_digit((string) $value)) {
                continue;
            }
            $id = (int) $value;
            if (in_array($id, $available, true)) {
                $ids[] = $id;
            }
        }
        return array_values(array_unique($ids));
    }

    private function chapterData(Request $request): array
    {
        return [
            'title' => $request->string('title'),
            'content' => $request->string('content'),
            'status' => $request->string('status', 'draft'),
        ];
    }

    private function validateChapter(array $data): Validator
    {
        return (new Validator($data))
            ->required('title', 'Judul bab')
            ->length('title', 'Judul bab', 3, 180)
            ->required('content', 'Isi bab')
            ->length('content', 'Isi bab', 50, 100000)
            ->required('status', 'Status bab')
            ->in('status', 'Status bab', ['draft', 'published']);
    }
}
