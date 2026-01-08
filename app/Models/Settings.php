<?php
/**
 * Модель для роботи з налаштуваннями
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Settings
{
    private PDO $db;
    private static ?array $cache = null;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Отримати всі налаштування
     */
    public function getAll(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM settings ORDER BY `key` ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Отримати значення налаштування за ключем
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (self::$cache === null) {
            self::$cache = [];
            $all = $this->getAll();
            foreach ($all as $setting) {
                self::$cache[$setting['key']] = $this->convertValue($setting['value'], $setting['type']);
            }
        }

        return self::$cache[$key] ?? $default;
    }

    /**
     * Встановити значення налаштування
     */
    public function set(string $key, mixed $value, string $type = 'string', ?string $description = null): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO settings (`key`, `value`, `type`, `description`, `updated_at`)
            VALUES (:key, :value, :type, :description, NOW())
            ON DUPLICATE KEY UPDATE 
                `value` = VALUES(`value`),
                `type` = VALUES(`type`),
                `description` = COALESCE(VALUES(`description`), `description`),
                `updated_at` = NOW()
        ");

        $stringValue = $this->convertToString($value, $type);

        $result = $stmt->execute([
            'key' => $key,
            'value' => $stringValue,
            'type' => $type,
            'description' => $description
        ]);

        self::$cache = null;

        return $result;
    }

    /**
     * Оновити кілька налаштувань
     */
    public function updateMultiple(array $settings): bool
    {
        $this->db->beginTransaction();
        try {
            foreach ($settings as $key => $data) {
                $value = $data['value'] ?? null;
                $type = $data['type'] ?? 'string';
                $description = $data['description'] ?? null;

                if (!$this->set($key, $value, $type, $description)) {
                    throw new \Exception("Failed to set setting: {$key}");
                }
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            \App\Core\Logger::error('settings_update_error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Перетворити значення в рядок для зберігання
     */
    private function convertToString(mixed $value, string $type): string
    {
        switch ($type) {
            case 'boolean':
                return $value ? '1' : '0';
            case 'integer':
                return (string)(int)$value;
            case 'json':
                return json_encode($value, JSON_UNESCAPED_UNICODE);
            default:
                return (string)$value;
        }
    }

    /**
     * Перетворити значення з рядка в потрібний тип
     */
    private function convertValue(string $value, string $type): mixed
    {
        switch ($type) {
            case 'boolean':
                return (bool)(int)$value;
            case 'integer':
                return (int)$value;
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }

    /**
     * Отримати налаштування за ключем (статичний метод для зручності)
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $model = new self();
        return $model->get($key, $default);
    }
}
