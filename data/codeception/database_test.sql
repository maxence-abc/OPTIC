-- Seed file generated from pg_dump COPY data (converted to INSERT)
-- Source: dump-app-202601230755.sql
-- Generated: 2026-01-23T07:00:23

TRUNCATE
    public.account_suspension,
    public.appointment,
    public.availability,
    public.doctrine_migration_versions,
    public.equipement,
    public.establishment,
    public.establishment_image,
    public.loyalty,
    public.opening_hour,
    public.reset_password_request,
    public.service,
    public."user",
    public.user_log
RESTART IDENTITY CASCADE;

-- Table: public.doctrine_migration_versions (8 rows)
INSERT INTO public.doctrine_migration_versions (version, executed_at, execution_time) VALUES
('DoctrineMigrations\\Version20260119100543', '2026-01-19 10:06:36', 160),
('DoctrineMigrations\\Version20260119120022', '2026-01-19 12:00:28', 11),
('DoctrineMigrations\\Version20260120155058', '2026-01-20 15:51:02', 1),
('DoctrineMigrations\\Version20260120161356', '2026-01-20 16:14:05', 42),
('DoctrineMigrations\\Version20260121084414', '2026-01-21 08:44:20', 55),
('DoctrineMigrations\\Version20260121095944', '2026-01-21 09:59:53', 9),
('DoctrineMigrations\\Version20260121175331', '2026-01-21 17:54:11', 121),
('DoctrineMigrations\\Version20260121180005', '2026-01-21 18:01:56', 10);

-- Table: public."user" (6 rows)
-- NOTE: on met establishment_id = NULL pour l'owner (id=12) afin de casser le cycle FK.
INSERT INTO public."user"
(id, establishment_id, email, roles, password, first_name, last_name, phone, specialization, is_active, created_at, update_at)
VALUES
(6, NULL, 'user@test.com', '["ROLE_ADMIN"]', '$2y$13$rKghRrqATW7ZMDaucph54OLDNUPhwOE6wMtuykotP5oQsCdRH0kQy', 'user', 'test', NULL, NULL, NULL, '2026-01-19 00:00:00', '2026-01-19 00:00:00'),
(9, NULL, 'dylan.azaz@gmail.com', '["ROLE_CLIENT"]', '$2y$13$3tKMhpKaNFECun0fNCekN.CCzHS5zS6SejjiiTlyJ4CJVDIGB5ndW', 'dylan', 'azaz', 0770086660, NULL, TRUE, '2026-01-19 12:28:45', '2026-01-19 12:28:45'),
(10, NULL, 'dylan.azazi@gmail.com', '["ROLE_CLIENT"]', '$2y$13$0H9T2J.uYpqU5uB1rWbpS.FCGcMcatHZvmb7iO49B7TrytN7jRLuO', 'dylan', 'azazi', 0770096660, NULL, TRUE, '2026-01-19 14:45:09', '2026-01-19 14:45:09'),
(11, NULL, 'jonathan.gravelat05@gmail.com', '["ROLE_CLIENT"]', '$2y$13$Dcv9NcQMRI.fiDYEeToT6OeUQNotZBjDUZjO4Bylu1LVZufUPT2L2', 'Jonathan', 'GRAVELAT', 0602072792, NULL, TRUE, '2026-01-20 08:26:53', '2026-01-20 08:26:53'),
(13, NULL, 'maxence@gmail.com', '["ROLE_CLIENT"]', '$2y$13$avNGiKaIc9ve8VIQlmmF0uui7v8bNQUWl8jDlNEm6xEfPBH4GG5ea', 'Maxence', 'Abric', 0770086660, NULL, TRUE, '2026-01-21 17:58:16', '2026-01-21 17:58:16'),
(12, NULL, 'maxence.abric87@gmail.com', '["ROLE_PRO"]', '$2y$13$T5Yk2e1E6bCOmBZEpYavF.6B4dIEQSgXOFCgf2c8mqamsY3NBTMeS', 'Maxence', 'Abric', 0770086660, NULL, TRUE, '2026-01-21 07:02:53', '2026-01-21 07:02:53');

-- Table: public.establishment (3 rows)
-- Maintenant que user(id=12) existe, owner_id=12 est OK.
INSERT INTO public.establishment
(id, owner_id, name, address, city, postal_code, description, professional_email, professional_phone, category)
VALUES
(2, 12, 'Impérial du thé bys', '216 A rue armand dutreix', 'Limoges', 87100, 'the', 'maxence.abric87@gmail.com', 0770086660, NULL),
(3, 12, 'Impérial du thé bysbys', '216 A rue armand dutreix', 'Limoges', 87100, 'eaf', 'maxence.abric87@gmail.com', 0770086660, NULL),
(4, 12, 'KFC', '219 A rue armand dutreix', 'Limoges', 87100, 'Fast Food', 'maxence.abric87@gmail.com', 0770086660, 'restaurant');

-- Réparation du cycle FK : on rattache maintenant le PRO (id=12) à son établissement (id=4)
UPDATE public."user"
SET establishment_id = 4
WHERE id = 12;

-- Table: public.service (3 rows)
INSERT INTO public.service
(id, establishment_id, name, description, duration, price, buffer_time)
VALUES
(2, 2, 'lala', 'eafcae', 10, 10.00, 5),
(3, 3, 'fac', 'fca', 5, 5.00, 5),
(4, 4, 'Menu burger', 'burger + frites + boisson', 5, 10.00, 0);

-- Table: public.establishment_image (3 rows)
INSERT INTO public.establishment_image
(id, establishment_id, path, "position", created_at)
VALUES
(2, 2, 'establishments/2/2f13b888e26c50002517.jpg', 0, '2026-01-21 09:09:49'),
(3, 3, 'establishments/3/f6372b2f1aa6991d03ed.jpg', 0, '2026-01-21 09:25:53'),
(4, 4, 'establishments/4/51e9ffc194ec194c23d5.jpg', 0, '2026-01-21 10:07:31');

-- Table: public.opening_hour (3 rows)
INSERT INTO public.opening_hour
(id, establishment_id, day_of_week, open_time, close_time)
VALUES
(2, 2, 'Monday', '10:59:00', '11:59:00'),
(3, 3, 'Wednesday', '10:21:00', '13:21:00'),
(4, 4, 'Monday', '12:06:00', '15:09:00');

-- Table: public.reset_password_request (2 rows)
INSERT INTO public.reset_password_request
(id, user_id, selector, hashed_token, requested_at, expires_at)
VALUES
(1, 11, 'GvjWjLPMIumyq70gxqtF', 'IHgioCYOJwckYNsGwSK0FnbX8y23kCNzcA4UpAaEnrI=', '2026-01-20 16:15:53', '2026-01-20 17:15:53'),
(2, 11, 'FjXti6VHryLaxRChXmSt', 'NYQyCCkI72XALN+DtVWw3aLRgcn+457EX/nwwJzVR0Y=', '2026-01-21 06:57:53', '2026-01-21 07:57:53');

-- Table: public.appointment (3 rows)
-- service + user existent, donc FK OK.
INSERT INTO public.appointment
(id, service_id, equipement_id, client_id, professional_id, date, start_time, end_time, status, created_at)
VALUES
(1, 4, NULL, 13, 12, '2026-01-26', '12:11:00', '12:16:00', 'pending', '2026-01-21 18:34:18'),
(2, 4, NULL, 13, 12, '2026-01-26', '12:16:00', '12:21:00', 'cancelled', '2026-01-21 18:34:31'),
(3, 4, NULL, 13, 12, '2026-01-26', '13:01:00', '13:06:00', 'cancelled', '2026-01-22 07:02:42');
