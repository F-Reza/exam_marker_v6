-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 13, 2026 at 01:15 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `educatalyst`
--

-- --------------------------------------------------------

--
-- Table structure for table `ai_settings`
--

CREATE TABLE `ai_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `provider` varchar(255) NOT NULL DEFAULT 'adaptive',
  `model` varchar(255) DEFAULT NULL,
  `api_key` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assessments`
--

CREATE TABLE `assessments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `grade` varchar(255) DEFAULT NULL,
  `exam_board` varchar(255) DEFAULT NULL,
  `assessment_date` date DEFAULT NULL,
  `total_marks` decimal(8,2) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `question_paper_path` varchar(255) NOT NULL,
  `mark_scheme_path` varchar(255) DEFAULT NULL,
  `written_answer_path` varchar(255) NOT NULL,
  `ai_summary` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ai_summary`)),
  `percentage` decimal(6,2) DEFAULT NULL,
  `grade_awarded` varchar(255) DEFAULT NULL,
  `public_report_token` varchar(64) DEFAULT NULL,
  `public_report_expires_at` timestamp NULL DEFAULT NULL,
  `finalised_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `assigned_teacher_id` bigint(20) UNSIGNED DEFAULT NULL,
  `processing_started_at` timestamp NULL DEFAULT NULL,
  `processing_completed_at` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `report_version` int(10) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assessment_attachments`
--

CREATE TABLE `assessment_attachments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `assessment_id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'insert',
  `path` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assessment_questions`
--

CREATE TABLE `assessment_questions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `assessment_id` bigint(20) UNSIGNED NOT NULL,
  `question_number` varchar(255) NOT NULL,
  `question_part` varchar(255) DEFAULT NULL,
  `max_marks` decimal(5,2) NOT NULL,
  `marking_points` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `organisation_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `subject_type` varchar(255) DEFAULT NULL,
  `subject_id` bigint(20) UNSIGNED DEFAULT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `ip_address` varchar(64) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_08_03_000001_create_plans_table', 1),
(5, '2026_08_03_000002_create_exam_marker_tables', 1),
(6, '2026_08_06_000003_create_parent_communications_table', 1),
(7, '2026_08_06_000004_add_public_report_tokens', 1),
(8, '2026_08_06_000005_create_support_and_settings_tables', 1),
(9, '2026_08_06_000006_create_notifications_table', 1),
(10, '2026_08_06_000007_add_role_account_structure', 1),
(11, '2026_08_09_000008_add_v5_operational_tables', 1),
(12, '2026_08_26_000009_add_v6_inserts_and_payments', 1),
(13, '2026_08_27_225544_create_system_settings_table', 1),
(14, '2026_08_28_011143_add_bkash_and_card_gateways', 1),
(15, '2026_08_28_092016_create_ai_settings_table', 1),
(16, '2026_08_28_102240_add_branding_fields_to_system_settings', 1),
(17, '2026_08_28_132046_add_transaction_id_to_payment_transactions_table', 1),
(18, '2026_09_11_000003_add_sub_question_support', 1),
(19, '2026_09_11_230026_create_assessment_questions_table', 1),
(20, '2026_09_11_230745_add_writing_rubric_to_question_results', 1);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) UNSIGNED NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parent_communications`
--

CREATE TABLE `parent_communications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `assessment_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED DEFAULT NULL,
  `recipient` varchar(255) NOT NULL,
  `channel` varchar(255) NOT NULL DEFAULT 'email',
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `message` text DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_gateway_settings`
--

CREATE TABLE `payment_gateway_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `provider` varchar(255) NOT NULL,
  `label` varchar(255) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 0,
  `environment` varchar(255) NOT NULL DEFAULT 'sandbox',
  `credentials` text DEFAULT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_gateway_settings`
--

INSERT INTO `payment_gateway_settings` (`id`, `provider`, `label`, `enabled`, `environment`, `credentials`, `options`, `created_at`, `updated_at`) VALUES
(1, 'bkash', 'bKash Bangladesh', 0, 'sandbox', '[]', '[]', '2026-09-12 17:15:32', '2026-09-12 17:15:32'),
(2, 'card', 'Debit / Credit Card', 0, 'sandbox', '[]', '[]', '2026-09-12 17:15:32', '2026-09-12 17:15:32'),
(3, 'paypal', 'PayPal', 0, 'sandbox', 'eyJpdiI6IkpBZkJmQ1ljTlo3eE1oS1hyTUZCaVE9PSIsInZhbHVlIjoiK2J3OHNYeWV1YmNGaE1CTGw2QmViUT09IiwibWFjIjoiYWY1YTY5OTQxYjQ4MzQzMDU4OWZkYzI1ZTc3ZGZlZTcwMWI1NDdjZDE1OGFhMDJmMDFhZjMxZjNjMTI4MzFkNyIsInRhZyI6IiJ9', '[]', '2026-09-12 17:15:32', '2026-09-12 17:15:32'),
(4, 'payu', 'PayU', 0, 'sandbox', 'eyJpdiI6IkpSQWxNNFdxazg5WTVHTm0yaTNHWHc9PSIsInZhbHVlIjoia3JMZTBaZVFPamw3aHV0emZJQ1o2UT09IiwibWFjIjoiMDllYzRjNGFkNmE2ZWIzNTkwYTFkNTE1ZmIyNzhkNzg4YTNmOWM3ZjU1ZDQ1MmY2MDUxZjNlNWM1NmU1ZmNhNyIsInRhZyI6IiJ9', '[]', '2026-09-12 17:15:32', '2026-09-12 17:15:32'),
(5, 'phonepe', 'PhonePe', 0, 'sandbox', 'eyJpdiI6IjJBbzl4czl2TitCK1lDRlBrVmRuQ3c9PSIsInZhbHVlIjoic0tzbTVVNHdvaXgrSlM2bnhlSXJZQT09IiwibWFjIjoiYzNmYmNjNDcxOWMzZTczZjM2ZGI1OTQ0ZmIzMzY0YWMzY2JmYzQxMDk5N2IyMTE0ZGExZDEyNjUwNzIwNDYxMCIsInRhZyI6IiJ9', '[]', '2026-09-12 17:15:32', '2026-09-12 17:15:32'),
(6, 'paytm', 'Paytm', 0, 'sandbox', 'eyJpdiI6IjMyZ1BtT2NBcC80M2E3VUFWcE0rS2c9PSIsInZhbHVlIjoieVByQXNmZHdjdWZRWWRlbUcyd2E1UT09IiwibWFjIjoiZGYzY2JlM2ExMGQ5OTk0Y2RmYTRlOTBjNzM5OGRlMGI2ZWM3ODU4ZTE4ZjVlZGM5NGFkYzg3ZWYwNWI5OGNmMSIsInRhZyI6IiJ9', '[]', '2026-09-12 17:15:32', '2026-09-12 17:15:32');

-- --------------------------------------------------------

--
-- Table structure for table `payment_transactions`
--

CREATE TABLE `payment_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `transaction_id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `plan_id` bigint(20) UNSIGNED NOT NULL,
  `gateway` varchar(255) NOT NULL,
  `reference` varchar(255) NOT NULL,
  `provider_reference` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'INR',
  `status` varchar(255) NOT NULL DEFAULT 'created',
  `provider_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`provider_payload`)),
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `plans`
--

CREATE TABLE `plans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `billing_period` varchar(255) NOT NULL DEFAULT 'monthly',
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`features`)),
  `limits` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`limits`)),
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `plans`
--

INSERT INTO `plans` (`id`, `name`, `slug`, `price`, `billing_period`, `features`, `limits`, `active`, `created_at`, `updated_at`) VALUES
(1, 'Free Mode', 'free', 0.00, 'lifetime', '{\"percentage\":false,\"grade\":false,\"graphs\":false,\"topic_analysis\":false,\"manual_mark_editing\":false,\"feedback_editing\":false,\"question_recheck\":false,\"result_report_download\":false,\"corrected_paper_download\":false,\"bulk_answer_upload\":false,\"bulk_student_import\":false,\"student_records\":false,\"parent_details\":false,\"parent_email\":false,\"parent_sms\":false,\"multiple_teachers\":false,\"teacher_assignment\":false,\"custom_branding\":false,\"student_portal\":false,\"parent_portal\":false}', '{\"paper_check_limit\":1,\"student_limit\":1,\"teacher_limit\":1,\"email_limit\":0,\"sms_limit\":0}', 1, '2026-09-12 17:15:32', '2026-09-12 17:15:32'),
(2, 'Mode 1 - Basic', 'mode-1', 399.00, 'monthly', '{\"percentage\":false,\"grade\":false,\"graphs\":false,\"topic_analysis\":false,\"manual_mark_editing\":false,\"feedback_editing\":false,\"question_recheck\":false,\"result_report_download\":false,\"corrected_paper_download\":false,\"bulk_answer_upload\":false,\"bulk_student_import\":false,\"student_records\":true,\"parent_details\":false,\"parent_email\":false,\"parent_sms\":false,\"multiple_teachers\":false,\"teacher_assignment\":false,\"custom_branding\":false,\"student_portal\":false,\"parent_portal\":false}', '{\"paper_check_limit\":10,\"student_limit\":5,\"teacher_limit\":1,\"email_limit\":0,\"sms_limit\":0}', 1, '2026-09-12 17:15:32', '2026-09-12 17:15:32'),
(3, 'Mode 2 - Professional', 'mode-2', 999.00, 'monthly', '{\"percentage\":true,\"grade\":true,\"graphs\":true,\"topic_analysis\":true,\"manual_mark_editing\":true,\"feedback_editing\":true,\"question_recheck\":true,\"result_report_download\":true,\"corrected_paper_download\":false,\"bulk_answer_upload\":true,\"bulk_student_import\":true,\"student_records\":true,\"parent_details\":false,\"parent_email\":false,\"parent_sms\":false,\"multiple_teachers\":false,\"teacher_assignment\":false,\"custom_branding\":false,\"student_portal\":false,\"parent_portal\":false}', '{\"paper_check_limit\":50,\"student_limit\":25,\"teacher_limit\":1,\"email_limit\":0,\"sms_limit\":0,\"recheck_limit\":100}', 1, '2026-09-12 17:15:32', '2026-09-12 17:15:32'),
(4, 'Mode 3 - Premium', 'mode-3', 1999.00, 'monthly', '{\"percentage\":true,\"grade\":true,\"graphs\":true,\"topic_analysis\":true,\"manual_mark_editing\":true,\"feedback_editing\":true,\"question_recheck\":true,\"result_report_download\":true,\"corrected_paper_download\":true,\"bulk_answer_upload\":true,\"bulk_student_import\":true,\"student_records\":true,\"parent_details\":true,\"parent_email\":true,\"parent_sms\":true,\"multiple_teachers\":true,\"teacher_assignment\":true,\"custom_branding\":false,\"student_portal\":false,\"parent_portal\":false}', '{\"paper_check_limit\":200,\"student_limit\":100,\"teacher_limit\":10,\"email_limit\":200,\"sms_limit\":200,\"recheck_limit\":500}', 1, '2026-09-12 17:15:32', '2026-09-12 17:15:32');

-- --------------------------------------------------------

--
-- Table structure for table `question_results`
--

CREATE TABLE `question_results` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `assessment_id` bigint(20) UNSIGNED NOT NULL,
  `question_number` varchar(255) NOT NULL,
  `topic` varchar(255) DEFAULT NULL,
  `max_marks` decimal(8,2) NOT NULL,
  `ai_marks` decimal(8,2) NOT NULL,
  `teacher_marks` decimal(8,2) DEFAULT NULL,
  `confidence` varchar(255) NOT NULL DEFAULT 'medium',
  `feedback` text DEFAULT NULL,
  `criteria` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`criteria`)),
  `teacher_comment` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'ai_checked',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `recheck_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `last_rechecked_at` timestamp NULL DEFAULT NULL,
  `question_part` varchar(255) DEFAULT NULL,
  `parent_question_number` varchar(255) DEFAULT NULL,
  `writing_rubric` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`writing_rubric`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `roll_number` varchar(255) DEFAULT NULL,
  `grade` varchar(255) DEFAULT NULL,
  `parent_name` varchar(255) DEFAULT NULL,
  `parent_email` varchar(255) DEFAULT NULL,
  `parent_mobile` varchar(255) DEFAULT NULL,
  `alternate_contact` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `login_user_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `user_id`, `name`, `roll_number`, `grade`, `parent_name`, `parent_email`, `parent_mobile`, `alternate_contact`, `created_at`, `updated_at`, `login_user_id`) VALUES
(1, 5, 'Aarav Sharma', '101', '10', 'Rahul Sharma', 'parent@example.com', '9876543210', NULL, '2026-09-12 17:15:34', '2026-09-12 17:15:34', 7);

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

CREATE TABLE `support_tickets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `subject` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL DEFAULT 'general',
  `priority` varchar(255) NOT NULL DEFAULT 'normal',
  `status` varchar(255) NOT NULL DEFAULT 'open',
  `message` text NOT NULL,
  `admin_reply` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `site_title` varchar(255) NOT NULL DEFAULT 'Exam Marker',
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `site_logo` varchar(255) DEFAULT NULL,
  `favicon` varchar(255) DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` text DEFAULT NULL,
  `language` varchar(255) NOT NULL DEFAULT 'en',
  `timezone` varchar(255) NOT NULL DEFAULT 'UTC'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `team_members`
--

CREATE TABLE `team_members` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `owner_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `mobile` varchar(255) DEFAULT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'teacher',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `usage_records`
--

CREATE TABLE `usage_records` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(255) NOT NULL,
  `quantity` decimal(12,2) NOT NULL DEFAULT 1.00,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) UNSIGNED DEFAULT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `owner_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `plan_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `mobile` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` enum('student','teacher','coaching') NOT NULL DEFAULT 'student',
  `organisation_name` varchar(255) DEFAULT NULL,
  `avatar_path` varchar(255) DEFAULT NULL,
  `notification_preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`notification_preferences`)),
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `free_check_used` tinyint(1) NOT NULL DEFAULT 0,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `account_status` varchar(255) NOT NULL DEFAULT 'active',
  `role_title` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `owner_user_id`, `plan_id`, `name`, `email`, `mobile`, `email_verified_at`, `password`, `user_type`, `organisation_name`, `avatar_path`, `notification_preferences`, `is_admin`, `free_check_used`, `remember_token`, `created_at`, `updated_at`, `account_status`, `role_title`) VALUES
(1, NULL, 4, 'Platform Admin', 'admin@example.com', '9000000001', NULL, '$2y$12$sOvr3XMwFr3zxoaSDSa8euhheDAT1jBk8GaKBGIvBX1jW/3NfaJvq', 'coaching', 'Exam Marker', NULL, NULL, 1, 0, NULL, '2026-09-12 17:15:33', '2026-09-12 17:15:33', 'active', NULL),
(2, NULL, 1, 'Free Teacher', 'free.teacher@example.com', '9000000002', NULL, '$2y$12$.f4Ketdmk/Qju7vmNtEBme7IcCXtioUcFeXTdumHpYqVKt3mumgdq', 'teacher', NULL, NULL, NULL, 0, 0, NULL, '2026-09-12 17:15:33', '2026-09-12 17:15:33', 'active', NULL),
(3, NULL, 2, 'Mode 1 User', 'mode1@example.com', '9000000003', NULL, '$2y$12$zFZkglfk5cGwP1WPNcIAO.w2T4rjobayLdwkwJ.drkThWmoZyXO1y', 'teacher', NULL, NULL, NULL, 0, 0, NULL, '2026-09-12 17:15:33', '2026-09-12 17:15:33', 'active', NULL),
(4, NULL, 3, 'Mode 2 Teacher', 'mode2@example.com', '9000000004', NULL, '$2y$12$P3wI6.TNU1W3ivQlevcUGuuY291Ij2x5NnlQ62QUfxvaUmjSWVBQG', 'teacher', NULL, NULL, NULL, 0, 0, NULL, '2026-09-12 17:15:33', '2026-09-12 17:15:33', 'active', NULL),
(5, NULL, 4, 'Mode 3 Coaching Admin', 'mode3@example.com', '9000000005', NULL, '$2y$12$Xb17y6GJ4iVqJz0z1M7g.uaNRYxPVoqiKaphiqnzvKcCQmOH7Jntq', 'coaching', 'Demo Coaching Class', NULL, NULL, 0, 0, NULL, '2026-09-12 17:15:34', '2026-09-12 17:15:34', 'active', NULL),
(6, 5, 4, 'Mode 3 Teacher', 'mode3.teacher@example.com', '9000000006', NULL, '$2y$12$IJsx2XqX9CcONcXDgw846OvWV0DtJAV7/rHllFCrHYr0fQFbDM0ee', 'teacher', NULL, NULL, NULL, 0, 0, NULL, '2026-09-12 17:15:34', '2026-09-12 17:15:34', 'active', 'Mathematics Teacher'),
(7, NULL, 1, 'Aarav Sharma', 'student@example.com', '9000000007', NULL, '$2y$12$DN9dFy4RrXOTKDI1PkaXx.moiLa2nFEijxjm0TazwESyJOSnFeYiq', 'student', NULL, NULL, NULL, 0, 0, NULL, '2026-09-12 17:15:34', '2026-09-12 17:15:34', 'active', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ai_settings`
--
ALTER TABLE `ai_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `assessments`
--
ALTER TABLE `assessments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `assessments_public_report_token_unique` (`public_report_token`),
  ADD KEY `assessments_user_id_foreign` (`user_id`),
  ADD KEY `assessments_student_id_foreign` (`student_id`),
  ADD KEY `assessments_assigned_teacher_id_foreign` (`assigned_teacher_id`);

--
-- Indexes for table `assessment_attachments`
--
ALTER TABLE `assessment_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assessment_attachments_assessment_id_foreign` (`assessment_id`);

--
-- Indexes for table `assessment_questions`
--
ALTER TABLE `assessment_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assessment_questions_assessment_id_foreign` (`assessment_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `audit_logs_user_id_foreign` (`user_id`),
  ADD KEY `audit_logs_organisation_user_id_created_at_index` (`organisation_user_id`,`created_at`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`);

--
-- Indexes for table `parent_communications`
--
ALTER TABLE `parent_communications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_communications_assessment_id_foreign` (`assessment_id`),
  ADD KEY `parent_communications_student_id_foreign` (`student_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `payment_gateway_settings`
--
ALTER TABLE `payment_gateway_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_gateway_settings_provider_unique` (`provider`);

--
-- Indexes for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_transactions_reference_unique` (`reference`),
  ADD UNIQUE KEY `payment_transactions_transaction_id_unique` (`transaction_id`),
  ADD KEY `payment_transactions_user_id_foreign` (`user_id`),
  ADD KEY `payment_transactions_plan_id_foreign` (`plan_id`);

--
-- Indexes for table `plans`
--
ALTER TABLE `plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `plans_slug_unique` (`slug`);

--
-- Indexes for table `question_results`
--
ALTER TABLE `question_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `question_results_assessment_id_foreign` (`assessment_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `students_user_id_foreign` (`user_id`),
  ADD KEY `students_login_user_id_foreign` (`login_user_id`);

--
-- Indexes for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `support_tickets_user_id_foreign` (`user_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `system_settings_key_unique` (`key`);

--
-- Indexes for table `team_members`
--
ALTER TABLE `team_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `team_members_owner_id_email_unique` (`owner_id`,`email`);

--
-- Indexes for table `usage_records`
--
ALTER TABLE `usage_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usage_records_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  ADD KEY `usage_records_user_id_type_created_at_index` (`user_id`,`type`,`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_mobile_unique` (`mobile`),
  ADD KEY `users_plan_id_foreign` (`plan_id`),
  ADD KEY `users_owner_user_id_foreign` (`owner_user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ai_settings`
--
ALTER TABLE `ai_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assessments`
--
ALTER TABLE `assessments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assessment_attachments`
--
ALTER TABLE `assessment_attachments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assessment_questions`
--
ALTER TABLE `assessment_questions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `parent_communications`
--
ALTER TABLE `parent_communications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_gateway_settings`
--
ALTER TABLE `payment_gateway_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `plans`
--
ALTER TABLE `plans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `question_results`
--
ALTER TABLE `question_results`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `team_members`
--
ALTER TABLE `team_members`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `usage_records`
--
ALTER TABLE `usage_records`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assessments`
--
ALTER TABLE `assessments`
  ADD CONSTRAINT `assessments_assigned_teacher_id_foreign` FOREIGN KEY (`assigned_teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `assessments_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `assessments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assessment_attachments`
--
ALTER TABLE `assessment_attachments`
  ADD CONSTRAINT `assessment_attachments_assessment_id_foreign` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assessment_questions`
--
ALTER TABLE `assessment_questions`
  ADD CONSTRAINT `assessment_questions_assessment_id_foreign` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_organisation_user_id_foreign` FOREIGN KEY (`organisation_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `parent_communications`
--
ALTER TABLE `parent_communications`
  ADD CONSTRAINT `parent_communications_assessment_id_foreign` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `parent_communications_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD CONSTRAINT `payment_transactions_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payment_transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `question_results`
--
ALTER TABLE `question_results`
  ADD CONSTRAINT `question_results_assessment_id_foreign` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_login_user_id_foreign` FOREIGN KEY (`login_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `students_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD CONSTRAINT `support_tickets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `team_members`
--
ALTER TABLE `team_members`
  ADD CONSTRAINT `team_members_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `usage_records`
--
ALTER TABLE `usage_records`
  ADD CONSTRAINT `usage_records_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_owner_user_id_foreign` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
