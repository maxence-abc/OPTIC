
-- ----------------------------
-- USERS
-- ----------------------------
-- Note: password = placeholders (format bcrypt-like) pour tests
INSERT INTO public."user"
(id, establishment_id, first_name, last_name, email, password, phone, specialization, is_active, created_at, update_at, roles)
VALUES
-- Admin
(1, NULL, 'Admin', 'Root', 'admin@test.local', '$2y$13$testtesttesttesttesttesttesttesttesttesttesttest', NULL, NULL, TRUE, '2026-01-10 09:00:00', NULL, '["ROLE_ADMIN"]'),

-- Pros
(2, NULL, 'Victor', 'Hugo', 'pro.victor@test.local', '$2y$13$testtesttesttesttesttesttesttesttesttesttesttest', '0611111111', 'Coiffeur', TRUE, '2026-01-10 09:05:00', NULL, '["ROLE_PRO"]'),
(3, NULL, 'dylan', 'azaz', 'dylan.azaz@gmail.com', 'azertyuiop', '0622222222', NULL, TRUE, '2026-01-10 09:06:00', NULL, '["ROLE_PRO"]'),
(4, NULL, 'Adrienne', 'Segalat', 'pro.adrienne@test.local', '$2y$13$testtesttesttesttesttesttesttesttesttesttesttest', '0633333333', 'Esthétique', TRUE, '2026-01-10 09:07:00', NULL, '["ROLE_PRO"]'),

-- Clients
(10, NULL, 'user', 'test', 'user@test.com', 'azertyuiop', '0644444444', NULL, TRUE, '2026-01-10 09:10:00', NULL, '["ROLE_CLIENT"]'),
(11, NULL, 'Bob', 'Durand', 'bob@test.local', '$2y$13$testtesttesttesttesttesttesttesttesttesttesttest', '0655555555', NULL, TRUE, '2026-01-10 09:11:00', NULL, '["ROLE_CLIENT"]'),

-- ----------------------------
-- ESTABLISHMENTS
-- ----------------------------
INSERT INTO public.establishment
(id, owner_id, name, address, city, postal_code, description, professional_email, professional_phone)
VALUES
(1, 2, 'CoiffeurSla', '46 avenue Gambetta', 'Limoges', '87100', 'Salon de coiffure', 'contact@coiffeursla.test', '0555000001'),
(2, 3, 'Bosphore Kebab', '246 rue Armand Dutreix', 'Limoges', '87100', 'Restauration rapide', 'contact@bosphore.test', '0555000002'),
(3, 4, 'Studio Adrienne', '12 rue de la Paix', 'Limoges', '87000', 'Soins esthétiques', 'contact@adrienne.test', '0555000003');

-- Lier certains users à leur établissement (pro rattaché)
UPDATE public."user" SET establishment_id = 1 WHERE id = 2;
UPDATE public."user" SET establishment_id = 2 WHERE id = 3;
UPDATE public."user" SET establishment_id = 3 WHERE id = 4;

-- ----------------------------
-- SERVICES
-- ----------------------------
INSERT INTO public.service
(id, establishment_id, name, description, duration, price, buffer_time)
VALUES
-- CoiffeurSla
(1, 1, 'Coupe homme', 'Shampoing + coiffage', 30, 20.00, 15),
(2, 1, 'Brushing', 'Brushing + shampoing', 45, 35.00, 10),
(3, 1, 'Coloration', 'Couleur + brushing', 90, 95.00, 20),

-- Bosphore
(4, 2, 'Menu kebab', 'Kebab + frites + boisson', 10, 11.50, NULL),
(5, 2, 'Menu assiette', 'Assiette + boisson', 12, 13.90, NULL),

-- Studio Adrienne
(6, 3, 'Soin visage', 'Nettoyage + hydratation', 60, 55.00, 10),
(7, 3, 'Épilation', 'Jambes complètes', 30, 30.00, 5);

-- ----------------------------
-- OPENING HOURS (exemple complet 7j pour 2 établissements)
-- ----------------------------
INSERT INTO public.opening_hour
(id, establishment_id, day_of_week, open_time, close_time)
VALUES
-- CoiffeurSla
(1, 1, 'Monday',    '08:00:00', '18:00:00'),

-- Studio Adrienne
(7, 3, 'Monday',    '10:00:00', '19:00:00'),
(8, 3, 'Tuesday',   '10:00:00', '19:00:00'),
(9, 3, 'Thursday',  '10:00:00', '19:00:00'),
(10,3, 'Friday',    '10:00:00', '19:00:00');

-- ----------------------------
-- APPOINTMENTS
-- IMPORTANT: pas de chevauchement pour un même pro / même équipement
-- sur status pending/confirmed
-- ----------------------------
INSERT INTO public.appointment
(id, client_id, service_id, equipement_id, date, start_time, end_time, status, created_at, professional_id)
VALUES
-- Pro Victor (id=2) chez CoiffeurSla (services 1..3), le 2026-01-20
(1, 10, 1, 1, '2026-01-20', '09:00:00', '09:30:00', 'confirmed', '2026-01-15 10:00:00', 2),
(2, 11, 2, 1, '2026-01-20', '09:45:00', '10:30:00', 'pending',   '2026-01-15 10:05:00', 2),
(3, 12, 3, 2, '2026-01-20', '10:45:00', '12:15:00', 'confirmed', '2026-01-15 10:10:00', 2),

-- Une annulation peut théoriquement chevaucher (car exclue du WHERE),
-- mais on reste propre
(4, 13, 1, 2, '2026-01-20', '13:00:00', '13:30:00', 'cancelled', '2026-01-15 10:12:00', 2),

-- Pro Adrienne (id=4) Studio Adrienne, le 2026-01-21 (cabines 3/4)
(5, 14, 6, 3, '2026-01-21', '10:00:00', '11:00:00', 'confirmed', '2026-01-15 11:00:00', 4),
(6, 12, 7, 4, '2026-01-21', '11:15:00', '11:45:00', 'pending',   '2026-01-15 11:05:00', 4),
(7, 10, 6, 3, '2026-01-21', '12:00:00', '13:00:00', 'confirmed', '2026-01-15 11:10:00', 4),

-- Pro Dylan (id=3) Bosphore: appointments possibles même sans equipement_id
(8, 11, 4, NULL, '2026-01-22', '12:00:00', '12:10:00', 'confirmed', '2026-01-15 12:00:00', 3),
(9, 10, 5, NULL, '2026-01-22', '12:15:00', '12:27:00', 'pending',   '2026-01-15 12:01:00', 3);

-- ----------------------------
-- USER LOG (exemples)
-- ----------------------------
-- INSERT INTO public.user_log
-- (id, related_user_id, action, description, ip_adress, created_at)
-- VALUES
-- (1, 10, 'LOGIN', 'Connexion client', '127.0.0.1', '2026-01-15 09:00:00'),
-- (2, 2,  'CREATE_APPOINTMENT', 'Création RDV #1', '127.0.0.1', '2026-01-15 10:00:01');

-- ----------------------------
-- ACCOUNT SUSPENSION (exemple)
-- ----------------------------
-- INSERT INTO public.account_suspension
-- (id, suspended_user_id, admin_user_id, reason, created_at, lifted_at)
-- VALUES
-- (1, 13, 1, 'Tests modération: suspension temporaire', '2026-01-15 08:00:00', '2026-01-16 08:00:00');

COMMIT;
