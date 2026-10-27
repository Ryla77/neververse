<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;
use Throwable;

final class Database
{
    private static ?PDO $pdo = null;
    private static int $savepointCounter = 0;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            (string) config('db.host'),
            (int) config('db.port'),
            (string) config('db.name')
        );

        self::$pdo = new PDO($dsn, (string) config('db.user'), (string) config('db.pass'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]);

        $offset = (new \DateTimeImmutable('now', new \DateTimeZone(date_default_timezone_get())))->format('P');
        self::$pdo->exec("SET time_zone = '{$offset}'");

        return self::$pdo;
    }

    public static function fetch(string $sql, array $params = []): ?array
    {
        $statement = self::run($sql, $params);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    public static function fetchColumn(string $sql, array $params = []): mixed
    {
        return self::run($sql, $params)->fetchColumn();
    }

    public static function execute(string $sql, array $params = []): PDOStatement
    {
        return self::run($sql, $params);
    }

    public static function insert(string $sql, array $params = []): int
    {
        self::run($sql, $params);
        return (int) self::pdo()->lastInsertId();
    }

    public static function transaction(callable $callback): mixed
    {
        $pdo = self::pdo();

        if ($pdo->inTransaction()) {
            $savepoint = 'nv_sp_' . self::$savepointCounter++;
            $pdo->exec('SAVEPOINT ' . $savepoint);
            try {
                $result = $callback($pdo);
                $pdo->exec('RELEASE SAVEPOINT ' . $savepoint);
                return $result;
            } catch (Throwable $e) {
                $pdo->exec('ROLLBACK TO SAVEPOINT ' . $savepoint);
                $pdo->exec('RELEASE SAVEPOINT ' . $savepoint);
                throw $e;
            }
        }

        $pdo->beginTransaction();
        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private static function run(string $sql, array $params): PDOStatement
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute($params);
        return $statement;
    }
}
