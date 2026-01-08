<?php
/**
 * Клас для роботи з базою даних через PDO
 * Використовує підготовлені запити для захисту від SQL-ін'єкцій
 */

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;
    private PDO $connection;

    /**
     * Приватний конструктор (Singleton)
     */
    private function __construct()
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_NAME,
                DB_CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];

            $this->connection = new PDO(
                $dsn,
                DB_USER,
                DB_PASS,
                $options
            );

            $timezone = defined('APP_TIMEZONE') ? APP_TIMEZONE : 'Europe/Kyiv';
            
            try {
                $dt = new \DateTime('now', new \DateTimeZone($timezone));
                $offset = $dt->format('P');
                
                try {
                    $this->connection->exec("SET time_zone = '" . $timezone . "'");
                } catch (PDOException $e) {
                    $this->connection->exec("SET time_zone = '" . $offset . "'");
                }
            } catch (\Exception $e) {
                try {
                    $this->connection->exec("SET time_zone = '+02:00'");
                    Logger::warning('Використано резервне значення часового поясу для MySQL: +02:00');
                } catch (PDOException $e2) {
                    Logger::warning('Не вдалося встановити часовий пояс для MySQL: ' . $e2->getMessage());
                }
            }
        } catch (PDOException $e) {
            Logger::error('Database connection failed: ' . $e->getMessage());
            throw new \RuntimeException('Database connection failed');
        }
    }

    /**
     * Отримати єдиний екземпляр підключення (Singleton)
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $db = new self();
            self::$instance = $db->connection;
        }
        return self::$instance;
    }

    /**
     * Забороняємо клонування
     */
    private function __clone() {}

    /**
     * Забороняємо десеріалізацію
     */
    public function __wakeup()
    {
        throw new \RuntimeException("Cannot unserialize singleton");
    }
}
