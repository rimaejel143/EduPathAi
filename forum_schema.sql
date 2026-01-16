-- =====================================================
-- STUDENT FORUM DATABASE SCHEMA
-- =====================================================
-- This SQL script creates the necessary tables for the Student Forum
-- with proper foreign key relationships to the existing users table.
-- 
-- Tables:
--   1. forum_categories - Forum topic categories
--   2. forum_posts - Discussion posts by students
--   3. forum_replies - Replies/comments on posts
--
-- Foreign Key Requirements:
--   - Your users table must have: user_id (INT, PRIMARY KEY), full_name (VARCHAR), user_type (VARCHAR)
-- =====================================================

-- =====================================================
-- 1. FORUM CATEGORIES TABLE
-- =====================================================
-- Stores forum categories/sections (e.g., General, Career Guidance, etc.)
CREATE TABLE forum_categories (
  category_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL UNIQUE,
  description TEXT DEFAULT NULL,
  sort_order INT DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  
  INDEX idx_sort_order (sort_order),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. FORUM POSTS TABLE
-- =====================================================
-- Stores discussion posts created by students
CREATE TABLE forum_posts (
  post_id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NOT NULL,
  user_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  body LONGTEXT NOT NULL,
  views INT DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (category_id) REFERENCES forum_categories(category_id) ON DELETE RESTRICT,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  
  INDEX idx_category (category_id),
  INDEX idx_user (user_id),
  INDEX idx_created_at (created_at),
  FULLTEXT INDEX idx_title_body (title, body)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. FORUM REPLIES TABLE
-- =====================================================
-- Stores replies/comments on posts
CREATE TABLE forum_replies (
  reply_id INT AUTO_INCREMENT PRIMARY KEY,
  post_id INT NOT NULL,
  user_id INT NOT NULL,
  body LONGTEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (post_id) REFERENCES forum_posts(post_id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  
  INDEX idx_post (post_id),
  INDEX idx_user (user_id),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SAMPLE DATA (OPTIONAL - Remove in production)
-- =====================================================

-- Insert sample categories
INSERT INTO forum_categories (name, description, sort_order) VALUES
('General Discussion', 'General topics and announcements', 1),
('Career Guidance', 'Career paths, internships, and job advice', 2),
('Programming & Tech', 'Programming questions, languages, and frameworks', 3),
('AI & Machine Learning', 'Discussion about AI, ML, and data science', 4),
('University Life', 'Student life, events, and experiences', 5);

-- Insert sample posts (assumes users with IDs 1, 2, 3 exist)
-- Replace user_id values with actual user IDs from your users table
INSERT INTO forum_posts (category_id, user_id, title, body) VALUES
(1, 1, 'Welcome to the Student Forum!', 'This is the official student forum where we can discuss various topics. Feel free to ask questions, share experiences, and help each other out!'),
(2, 2, 'Career tips for fresh graduates', 'I recently graduated and landed my first job. I would love to share some tips and answer any questions about the job search process.'),
(3, 3, 'Best programming languages to learn in 2026', 'What are your thoughts on the best programming languages to focus on this year? I''m interested in both backend and frontend development.');

-- Insert sample replies (assumes the posts above were created)
-- Adjust post_id values based on the actual post IDs created
INSERT INTO forum_replies (post_id, user_id, body) VALUES
(1, 2, 'Thanks for creating this forum! Looking forward to discussions here.'),
(1, 3, 'Great initiative! Excited to be part of this community.'),
(2, 1, 'Congratulations on your new job! Would love to hear more about your experience.');

-- =====================================================
-- OPTIONAL: ALTER QUERIES FOR UX IMPROVEMENTS
-- =====================================================
-- If you want to display author names in the forum, you can:
--
-- 1. Modify get_posts.php to include:
--    SELECT p.post_id, p.category_id, p.user_id, p.title, p.body, p.created_at,
--           u.full_name,  -- Add this line
--           (SELECT COUNT(*) FROM forum_replies r WHERE r.post_id = p.post_id) AS reply_count
--    FROM forum_posts p
--    JOIN users u ON p.user_id = u.user_id  -- Add this JOIN
--    WHERE p.category_id = ? ...
--
-- 2. Modify get_replies.php to include:
--    SELECT r.reply_id, r.post_id, r.user_id, r.body, r.created_at,
--           u.full_name  -- Add this line
--    FROM forum_replies r
--    JOIN users u ON r.user_id = u.user_id  -- Add this JOIN
--    WHERE r.post_id = ? ...
--
-- 3. Update forum.js to display the author names in the UI:
--    meta.textContent = `By ${p.full_name} • ${p.reply_count} replies • ${p.created_at}`;
--    (Instead of: `By user ${p.user_id} • ...`)
--
-- This requires modifying the PHP files to include user joins and JS to display names.
-- =====================================================
