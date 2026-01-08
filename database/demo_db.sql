-- Схема бази даних для системи онлайн-тестування з демо даними
-- Версія: 1.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+02:00";

-- Таблиця спроб проходження тесту
CREATE TABLE `attempts` (
  `id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('in_progress','completed','abandoned') NOT NULL DEFAULT 'in_progress',
  `score` decimal(10,2) DEFAULT NULL,
  `max_score` decimal(10,2) DEFAULT NULL,
  `started_at` datetime NOT NULL,
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблиця відповідей на питання
CREATE TABLE `attempt_answers` (
  `id` int(11) NOT NULL,
  `attempt_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer_data` text NOT NULL COMMENT 'JSON або текст відповіді',
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблиця файлів
CREATE TABLE `files` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Дамп даних таблиці `files`
INSERT INTO `files` (`id`, `name`, `original_name`, `file_path`, `file_size`, `mime_type`, `uploaded_by`, `created_at`, `updated_at`) VALUES
(2, 'file_696015a5471f59.80402227.pdf', 'Math.pdf', '/home/vol19_2/infinityfree.com/if0_40859659/htdocs/public/uploads/files/file_696015a5471f59.80402227.pdf', 17318, 'application/pdf', 2, '2026-01-08 12:37:57', NULL),
(3, 'file_69601825882719.02413557.pdf', 'History.pdf', '/home/vol19_2/infinityfree.com/if0_40859659/htdocs/public/uploads/files/file_69601825882719.02413557.pdf', 17318, 'application/pdf', 3, '2026-01-08 12:48:37', NULL);

-- Таблиця призначень файлів студентам
CREATE TABLE `file_assignments` (
  `id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Дамп даних таблиці `file_assignments`
INSERT INTO `file_assignments` (`id`, `file_id`, `user_id`, `group_id`, `created_at`) VALUES
(3, 2, NULL, 1, '2026-01-08 12:37:57'),
(4, 2, NULL, 2, '2026-01-08 12:37:57'),
(5, 3, NULL, 1, '2026-01-08 12:48:37');

-- Таблиця груп
CREATE TABLE `groups` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Дамп даних таблиці `groups`
INSERT INTO `groups` (`id`, `name`, `description`, `teacher_id`, `created_at`, `updated_at`) VALUES
(1, 'Група студентів №1', 'Студенти вивчають і Історію і Математику.', NULL, '2026-01-08 12:26:02', '2026-01-08 12:32:23'),
(2, 'Група студентів №2', 'Студенти вивчають лише Математику.', NULL, '2026-01-08 12:31:46', '2026-01-08 12:33:09');

-- --------------------------------------------------------

--
-- Структура таблицы `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `created_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('single_choice','multiple_choice','true_false','short_answer') NOT NULL,
  `points` decimal(5,2) NOT NULL DEFAULT 1.00,
  `order_index` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `questions`
--

INSERT INTO `questions` (`id`, `test_id`, `question_text`, `question_type`, `points`, `order_index`, `created_at`, `updated_at`) VALUES
(1, 1, 'Скільки буде 2+2?', 'single_choice', '1.00', 0, '2026-01-08 12:40:15', '2026-01-08 12:40:23'),
(2, 1, 'Які з чисел є парними?', 'multiple_choice', '1.00', 0, '2026-01-08 12:41:34', NULL),
(3, 1, 'Чи ділиться число 15 на 4 без остачі?', 'true_false', '1.00', 0, '2026-01-08 12:42:28', NULL),
(4, 1, 'Скільки хвилин у 1 годині?', 'short_answer', '1.00', 0, '2026-01-08 12:43:25', NULL),
(5, 2, 'Хто запровадив християнство як державну релігію в Київській Русі?', 'single_choice', '1.00', 0, '2026-01-08 12:50:49', '2026-01-08 12:50:55'),
(10, 2, 'Хто з перелічених діячів був князем Київської Русі?', 'multiple_choice', '1.00', 0, '2026-01-08 12:58:02', NULL),
(11, 2, 'Чи був Богдан Хмельницький гетьманом Війська Запорізького?', 'true_false', '1.00', 0, '2026-01-08 12:58:41', NULL),
(12, 2, 'Хто був першим гетьманом Війська Запорозького?', 'short_answer', '1.00', 0, '2026-01-08 12:59:18', NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `question_options`
--

CREATE TABLE `question_options` (
  `id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `option_text` text NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0,
  `order_index` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `question_options`
--

INSERT INTO `question_options` (`id`, `question_id`, `option_text`, `is_correct`, `order_index`) VALUES
(5, 1, '3', 1, 0),
(6, 1, '7', 0, 1),
(7, 1, '4', 0, 2),
(8, 1, '8', 0, 3),
(9, 2, '4', 1, 0),
(10, 2, '7', 0, 1),
(11, 2, '10', 1, 2),
(12, 2, '13', 0, 3),
(13, 3, 'true', 0, 0),
(14, 3, 'false', 1, 1),
(15, 4, '60', 1, 0),
(20, 5, 'Ярослав Мудрий', 1, 0),
(21, 5, 'Володимир Великий', 0, 1),
(22, 5, 'Олег', 0, 2),
(23, 5, 'Святослав', 0, 3),
(40, 10, 'Володимир Великий', 1, 0),
(41, 10, 'Ярослав Мудрий', 1, 1),
(42, 10, 'Богдан Хмельницький', 0, 2),
(43, 10, 'Данило Галицький', 0, 3),
(44, 11, 'true', 1, 0),
(45, 11, 'false', 0, 1),
(46, 12, 'Богдан Хмельницький', 1, 0);

-- --------------------------------------------------------

--
-- Структура таблицы `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `type` enum('string','integer','boolean','json') NOT NULL DEFAULT 'string',
  `description` text DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `settings`
--

INSERT INTO `settings` (`id`, `key`, `value`, `type`, `description`, `updated_at`) VALUES
(1, 'site_name', 'Система онлайн-тестування', 'string', 'Назва сайту', '2026-01-08 22:18:33'),
(2, 'allowed_file_types', 'pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar,7z,jpg,jpeg,png,gif', 'string', 'Дозволені типи файлів (через кому)', '2026-01-08 22:18:33'),
(3, 'max_file_size', '10485760', 'integer', 'Максимальний розмір файлу в байтах (10 MB)', '2026-01-08 22:18:33'),
(4, 'smtp_enabled', '0', 'boolean', 'Увімкнути SMTP для відправки листів', '2026-01-08 22:18:33'),
(5, 'smtp_host', '', 'string', 'SMTP хост', '2026-01-08 22:18:33'),
(6, 'smtp_port', '587', 'integer', 'SMTP порт', '2026-01-08 22:18:33'),
(7, 'smtp_username', '', 'string', 'SMTP ім\'я користувача', '2026-01-08 22:18:33'),
(8, 'smtp_password', '', 'string', 'SMTP пароль', '2026-01-08 22:18:33'),
(9, 'smtp_encryption', 'tls', 'string', 'SMTP шифрування (tls/ssl)', '2026-01-08 22:18:33'),
(10, 'smtp_from_email', '', 'string', 'Email відправника', '2026-01-08 22:18:33'),
(11, 'smtp_from_name', '', 'string', 'Ім\'я відправника', '2026-01-08 22:18:33'),
(12, 'items_per_page', '10', 'integer', 'Кількість елементів на сторінці за замовчуванням', '2026-01-08 22:18:33'),
(13, 'maintenance_mode', '0', 'boolean', 'Режим обслуговування', '2026-01-08 22:18:33'),
(14, 'color_primary', '#6366f1', 'string', 'Основний колір (Primary)', '2026-01-08 22:18:33'),
(15, 'color_primary_dark', '#4f46e5', 'string', 'Темний основний колір (Primary Dark)', '2026-01-08 22:18:33'),
(16, 'color_primary_light', '#818cf8', 'string', 'Світлий основний колір (Primary Light)', '2026-01-08 22:18:33'),
(17, 'color_secondary', '#64748b', 'string', 'Вторинний колір (Secondary)', '2026-01-08 22:18:33'),
(18, 'color_success', '#10b981', 'string', 'Колір успіху (Success)', '2026-01-08 22:18:33'),
(19, 'color_danger', '#ef4444', 'string', 'Колір небезпеки (Danger)', '2026-01-08 22:18:33'),
(20, 'color_warning', '#f59e0b', 'string', 'Колір попередження (Warning)', '2026-01-08 22:18:33'),
(21, 'color_info', '#06b6d4', 'string', 'Інформаційний колір (Info)', '2026-01-08 22:18:33'),
(22, 'css_version', '1767903513', 'integer', 'Версія CSS для інвалідації кешу', '2026-01-08 22:18:33');

-- --------------------------------------------------------

--
-- Структура таблицы `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `subjects`
--

INSERT INTO `subjects` (`id`, `name`, `description`, `teacher_id`, `created_at`, `updated_at`) VALUES
(1, 'Математика', 'Математика — це точна наука про кількісні співвідношення, просторові форми, структури та зміни, яка виникла для практичних потреб.', NULL, '2026-01-08 12:26:38', '2026-01-08 12:27:03'),
(2, 'Історія', 'Історія - це наука, що вивчає минуле через аналіз джерел, а також сукупність подій, які відбувалися в минулому та їх послідовний розвиток.', NULL, '2026-01-08 12:27:37', '2026-01-08 12:49:30');

-- --------------------------------------------------------

--
-- Структура таблицы `teacher_groups`
--

CREATE TABLE `teacher_groups` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `teacher_groups`
--

INSERT INTO `teacher_groups` (`id`, `teacher_id`, `group_id`, `created_at`) VALUES
(5, 3, 1, '2026-01-08 12:32:23'),
(6, 2, 1, '2026-01-08 12:32:23'),
(8, 2, 2, '2026-01-08 12:33:09');

-- --------------------------------------------------------

--
-- Структура таблицы `teacher_subjects`
--

CREATE TABLE `teacher_subjects` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `teacher_subjects`
--

INSERT INTO `teacher_subjects` (`id`, `teacher_id`, `subject_id`, `created_at`) VALUES
(3, 2, 1, '2026-01-08 12:27:53'),
(4, 3, 2, '2026-01-08 12:28:42');

-- --------------------------------------------------------

--
-- Структура таблицы `tests`
--

CREATE TABLE `tests` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `duration` int(11) DEFAULT NULL COMMENT 'Тривалість у хвилинах',
  `max_attempts` int(11) NOT NULL DEFAULT 1,
  `passing_score` decimal(5,2) NOT NULL DEFAULT 60.00 COMMENT 'Прохідний бал у відсотках',
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `tests`
--

INSERT INTO `tests` (`id`, `title`, `description`, `subject_id`, `duration`, `max_attempts`, `passing_score`, `start_date`, `end_date`, `is_published`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Тест з Математики', 'Тест з математики - це перевірка знань, умінь і навичок учнів з математичних тем.', 1, 5, 100, '60.00', NULL, NULL, 1, 2, '2026-01-08 12:39:42', '2026-01-08 12:43:38'),
(2, 'Тест з Історії', 'Тест з Історії - це перевірка знань учнів про історичні події, постаті, дати та процеси розвитку української держави та суспільства.', 2, 5, 100, '60.00', NULL, NULL, 1, 3, '2026-01-08 12:47:05', '2026-01-08 13:00:56');

-- --------------------------------------------------------

--
-- Структура таблицы `test_assignments`
--

CREATE TABLE `test_assignments` (
  `id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'Якщо призначено конкретному студенту',
  `group_id` int(11) DEFAULT NULL COMMENT 'Якщо призначено групі',
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `test_assignments`
--

INSERT INTO `test_assignments` (`id`, `test_id`, `user_id`, `group_id`, `created_at`) VALUES
(1, 1, NULL, 1, '2026-01-08 12:43:59'),
(2, 1, NULL, 2, '2026-01-08 12:43:59'),
(3, 2, NULL, 1, '2026-01-08 13:00:26');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `role` enum('admin','teacher','student') NOT NULL DEFAULT 'student',
  `status` enum('active','banned') NOT NULL DEFAULT 'active',
  `email_notifications` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Включені email повідомлення (1 - включені, 0 - виключені)',
  `group_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `first_name`, `last_name`, `role`, `status`, `email_notifications`, `group_id`, `created_at`, `updated_at`) VALUES
(1, 'admin@edu.ua', '$2y$10$bjmxFiSmOt4MsnZte.0By.pqTaj2DM2gataRTjEXkKjJOSaTMwLba', 'Адміністратор', '№1', 'admin', 'active', 1, NULL, '2026-01-08 12:22:54', '2026-01-08 12:29:23'),
(2, 'teacher@edu.ua', '$2y$10$EA0yd3zjOtbQMchW1.U55.GMthtLS1JFLLmsY5I3eoZ7ZnnAILDoS', 'Вчитель', 'Математики', 'teacher', 'active', 1, NULL, '2026-01-08 12:24:44', '2026-01-08 12:27:53'),
(3, 'teacher2@edu.ua', '$2y$10$7Nnf7KJu.HCUSA.8Pbqn6e1mezYA.3QIGRiwiTK0cIGXzrZMXKrTu', 'Вчитель', 'Історії', 'teacher', 'active', 1, NULL, '2026-01-08 12:28:42', NULL),
(4, 'student@edu.ua', '$2y$10$pTe8jsXFv0vk.UwW4SdBI.4PoMe5w/ugEZN0g/qOUB/US0SGOhr9m', 'Студент', '№1', 'student', 'active', 1, 1, '2026-01-08 12:31:15', NULL),
(5, 'student2@edu.ua', '$2y$10$NbJe2e5LjIbGW1LPJ31IhOPeIsHu5hsNlup2qqJjt2XpstLdHwih2', 'Студент', '№2', 'student', 'active', 1, 2, '2026-01-08 12:33:58', NULL);

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `attempts`
--
ALTER TABLE `attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_test` (`test_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_started` (`started_at`);

--
-- Индексы таблицы `attempt_answers`
--
ALTER TABLE `attempt_answers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attempt_question` (`attempt_id`,`question_id`),
  ADD KEY `idx_attempt` (`attempt_id`),
  ADD KEY `idx_question` (`question_id`);

--
-- Индексы таблицы `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_uploaded_by` (`uploaded_by`);

--
-- Индексы таблицы `file_assignments`
--
ALTER TABLE `file_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_file_user` (`file_id`,`user_id`),
  ADD UNIQUE KEY `unique_file_group` (`file_id`,`group_id`),
  ADD KEY `idx_file` (`file_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_group` (`group_id`);

--
-- Индексы таблицы `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_teacher` (`teacher_id`);

--
-- Индексы таблицы `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_token` (`token`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Индексы таблицы `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_test` (`test_id`),
  ADD KEY `idx_order` (`test_id`,`order_index`);

--
-- Индексы таблицы `question_options`
--
ALTER TABLE `question_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_question` (`question_id`),
  ADD KEY `idx_order` (`question_id`,`order_index`);

--
-- Индексы таблицы `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_key` (`key`);

--
-- Индексы таблицы `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_teacher` (`teacher_id`);

--
-- Индексы таблицы `teacher_groups`
--
ALTER TABLE `teacher_groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_teacher_group` (`teacher_id`,`group_id`),
  ADD KEY `idx_teacher` (`teacher_id`),
  ADD KEY `idx_group` (`group_id`);

--
-- Индексы таблицы `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_teacher_subject` (`teacher_id`,`subject_id`),
  ADD KEY `idx_teacher` (`teacher_id`),
  ADD KEY `idx_subject` (`subject_id`);

--
-- Индексы таблицы `tests`
--
ALTER TABLE `tests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_subject` (`subject_id`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_published` (`is_published`),
  ADD KEY `idx_dates` (`start_date`,`end_date`);

--
-- Индексы таблицы `test_assignments`
--
ALTER TABLE `test_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_assignment` (`test_id`,`user_id`,`group_id`),
  ADD KEY `idx_test` (`test_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_group` (`group_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_group` (`group_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `attempts`
--
ALTER TABLE `attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `attempt_answers`
--
ALTER TABLE `attempt_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `files`
--
ALTER TABLE `files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `file_assignments`
--
ALTER TABLE `file_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `groups`
--
ALTER TABLE `groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT для таблицы `question_options`
--
ALTER TABLE `question_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT для таблицы `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT для таблицы `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `teacher_groups`
--
ALTER TABLE `teacher_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT для таблицы `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `tests`
--
ALTER TABLE `tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `test_assignments`
--
ALTER TABLE `test_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `attempts`
--
ALTER TABLE `attempts`
  ADD CONSTRAINT `fk_attempts_test` FOREIGN KEY (`test_id`) REFERENCES `tests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_attempts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `attempt_answers`
--
ALTER TABLE `attempt_answers`
  ADD CONSTRAINT `fk_answers_attempt` FOREIGN KEY (`attempt_id`) REFERENCES `attempts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_answers_question` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `files`
--
ALTER TABLE `files`
  ADD CONSTRAINT `fk_files_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `file_assignments`
--
ALTER TABLE `file_assignments`
  ADD CONSTRAINT `fk_file_assignments_file` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_file_assignments_group` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_file_assignments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `groups`
--
ALTER TABLE `groups`
  ADD CONSTRAINT `fk_groups_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `fk_questions_test` FOREIGN KEY (`test_id`) REFERENCES `tests` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `question_options`
--
ALTER TABLE `question_options`
  ADD CONSTRAINT `fk_options_question` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `fk_subjects_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `teacher_groups`
--
ALTER TABLE `teacher_groups`
  ADD CONSTRAINT `fk_teacher_groups_group` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_teacher_groups_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD CONSTRAINT `fk_teacher_subjects_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_teacher_subjects_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `tests`
--
ALTER TABLE `tests`
  ADD CONSTRAINT `fk_tests_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tests_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `test_assignments`
--
ALTER TABLE `test_assignments`
  ADD CONSTRAINT `fk_assignments_group` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_assignments_test` FOREIGN KEY (`test_id`) REFERENCES `tests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_assignments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_group` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
