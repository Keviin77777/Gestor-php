<?php
/**
 * Classe de gerenciamento de banco de dados
 */
class Database
{
    private static ?PDO $connection = null;

    /**
     * Conectar ao banco de dados
     */
    public static function connect(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        try {
            $host = env('DB_HOST', 'localhost');
            $port = env('DB_PORT', '3306');
            $dbname = env('DB_NAME', 'ultragestor_php');
            $user = env('DB_USER', 'root');
            $pass = env('DB_PASS', '');

            if (empty($host) || empty($dbname)) {
                throw new Exception('Configurações do banco de dados não encontradas. Verifique o arquivo .env');
            }

            $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

            self::$connection = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]);

            return self::$connection;
        }
        catch (PDOException $e) {
            logError('Database connection failed: ' . $e->getMessage());
            throw new Exception('Erro ao conectar ao banco de dados: ' . $e->getMessage());
        }
        catch (Exception $e) {
            logError('Database configuration error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Executar query com prepared statement
     */
    public static function query(string $sql, array $params = []): PDOStatement
    {
        try {
            $stmt = self::connect()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        }
        catch (PDOException $e) {
            logError('Query failed: ' . $e->getMessage(), ['sql' => $sql, 'params' => $params]);
            throw new Exception('Erro ao executar query: ' . $e->getMessage());
        }
    }

    /**
     * Buscar um registro
     */
    public static function fetch(string $sql, array $params = [])
    {
        return self::query($sql, $params)->fetch();
    }

    /**
     * Buscar todos os registros
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    /**
     * Buscar um único registro (Alias para fetch)
     */
    public static function fetchOne(string $sql, array $params = [])
    {
        return self::fetch($sql, $params);
    }

    /**
     * Executar SQL sem retorno de dados
     */
    public static function execute(string $sql, array $params = []): bool
    {
        self::query($sql, $params);
        return true;
    }

    /**
     * Inserir registro
     */
    public static function insert(string $sql, array $params = []): string
    {
        self::query($sql, $params);
        return self::connect()->lastInsertId();
    }

    /**
     * Atualizar registro
     */
    public static function update(string $table, array $data, string $where, array $whereParams = []): bool
    {
        $set = [];
        foreach (array_keys($data) as $key) {
            $set[] = "$key = :$key";
        }
        $setStr = implode(', ', $set);

        $sql = "UPDATE $table SET $setStr WHERE $where";
        $params = array_merge($data, $whereParams);

        self::query($sql, $params);
        return true;
    }

    /**
     * Deletar registro
     */
    public static function delete(string $table, string $where, array $params = []): bool
    {
        $sql = "DELETE FROM $table WHERE $where";
        self::query($sql, $params);
        return true;
    }

    /**
     * Iniciar transação
     */
    public static function beginTransaction(): void
    {
        self::connect()->beginTransaction();
    }

    /**
     * Commit transação
     */
    public static function commit(): void
    {
        self::connect()->commit();
    }

    /**
     * Rollback transação
     */
    public static function rollback(): void
    {
        self::connect()->rollBack();
    }
}
