-- BetLedger Sample Data
-- Seed database with realistic example data for testing

-- Insert test user
INSERT INTO users (username, email, password_hash, currency, timezone, odds_format, date_format, bankroll_start) VALUES
('testuser', 'test@example.com', '$2y$12$abcd1234', 'USD', 'UTC', 'decimal', 'Y-m-d', 1000);

-- Get user ID for references
SET @user_id = LAST_INSERT_ID();

-- Insert bookmakers
INSERT INTO bookmakers (user_id, name, url, account_balance, bonus_balance, notes) VALUES
(@user_id, 'Bet365', 'https://www.bet365.com', 500.00, 50.00, 'Main account'),
(@user_id, 'DraftKings', 'https://www.draftkings.com', 750.00, 25.00, 'Sports betting'),
(@user_id, 'BetMGM', 'https://www.betmgm.com', 300.00, 0, 'New account'),
(@user_id, 'FanDuel', 'https://www.fanduel.com', 650.00, 100.00, 'Promotions ongoing');

-- Insert competitions
INSERT INTO competitions (sport_id, name, country) VALUES
(1, 'Premier League', 'England'),
(1, 'La Liga', 'Spain'),
(1, 'Serie A', 'Italy'),
(1, 'Bundesliga', 'Germany'),
(2, 'ATP Masters', 'International'),
(3, 'NBA', 'USA'),
(4, 'NHL', 'USA/Canada'),
(5, 'MLB', 'USA');

-- Insert tags
INSERT INTO tags (user_id, name, color) VALUES
(@user_id, 'High Confidence', '#00d084'),
(@user_id, 'Value Bet', '#3498db'),
(@user_id, 'Live Bet', '#ffa502'),
(@user_id, 'Risky', '#ff4757'),
(@user_id, 'System', '#9b59b6');

-- Insert tipsters
INSERT INTO tipsters (user_id, name, source_url, notes) VALUES
(@user_id, 'Football Expert', 'https://example.com/expert1', 'Specializes in Premier League'),
(@user_id, 'Stats Analyst', 'https://example.com/analyst', 'Data-driven approach'),
(@user_id, 'In-Play Specialist', 'https://example.com/inplay', 'Live betting focus');

-- Insert sample bets
INSERT INTO bets (user_id, bookmaker_id, sport_id, competition_id, event_name, event_date, bet_type, selection, odds, stake, potential_return, status, actual_return, notes, created_at, settled_at) VALUES
(@user_id, 1, 1, 1, 'Manchester United vs Liverpool', '2026-03-15 20:00', 'single', 'Manchester United Win', 1.85, 100.00, 185.00, 'won', 185.00, 'Great performance', '2026-03-01 10:30:00', '2026-03-16 10:00:00'),
(@user_id, 1, 1, 1, 'Chelsea vs Arsenal', '2026-03-14 19:45', 'single', 'Draw', 3.50, 50.00, 175.00, 'lost', 0, 'Unexpected result', '2026-03-01 11:00:00', '2026-03-15 10:00:00'),
(@user_id, 2, 3, 6, 'Lakers vs Celtics', '2026-03-20 22:30', 'double', 'Lakers -5 & Over 210.5', 1.95, 200.00, 390.00, 'won', 390.00, 'Both legs hit', '2026-03-02 14:30:00', '2026-03-21 10:00:00'),
(@user_id, 3, 2, 5, 'Djokovic vs Nadal', '2026-03-25 14:00', 'accumulator', 'Djokovic Win + Federer Win + Murray Win', 5.20, 75.00, 390.00, 'lost', 0, 'Murray lost early', '2026-03-05 09:00:00', '2026-03-26 10:00:00'),
(@user_id, 1, 1, 2, 'Real Madrid vs Barcelona', '2026-04-01 21:00', 'single', 'Real Madrid Win', 2.10, 150.00, 315.00, 'won', 315.00, 'Dominant performance', '2026-03-10 16:45:00', '2026-04-02 10:00:00'),
(@user_id, 4, 1, 1, 'Tottenham vs Manchester City', '2026-04-05 19:30', 'single', 'Manchester City Win', 1.65, 250.00, 412.50, 'won', 412.50, 'Expected result', '2026-03-15 12:00:00', '2026-04-06 10:00:00'),
(@user_id, 2, 3, 6, 'Golden State Warriors vs Denver Nuggets', '2026-04-10 22:00', 'single', 'Warriors -3.5', 1.90, 100.00, 190.00, 'lost', 0, 'Close game', '2026-03-20 18:30:00', '2026-04-11 10:00:00'),
(@user_id, 1, 1, 3, 'AC Milan vs Inter Milan', '2026-04-12 20:00', 'single', 'Inter Win', 2.25, 80.00, 180.00, 'pending', NULL, 'Serie A derby', '2026-04-01 10:00:00', NULL),
(@user_id, 3, 4, 7, 'Toronto Maple Leafs vs Boston Bruins', '2026-04-15 19:00', 'single', 'Toronto Win', 1.95, 120.00, 234.00, 'pending', NULL, 'Playoff contender', '2026-04-02 15:00:00', NULL),
(@user_id, 4, 5, 8, 'New York Yankees vs Boston Red Sox', '2026-05-01 20:00', 'single', 'Yankees Total Runs Over 4.5', 1.75, 200.00, 350.00, 'pending', NULL, 'MLB regular season', '2026-04-20 11:00:00', NULL),
(@user_id, 1, 1, 1, 'Everton vs West Ham', '2026-04-18 15:00', 'single', 'Everton Win', 2.40, 75.00, 180.00, 'void', NULL, 'Match abandoned', '2026-04-05 09:00:00', '2026-04-18 16:00:00'),
(@user_id, 2, 1, 1, 'Southampton vs Brighton', '2026-04-22 20:00', 'single', 'Both Teams to Score', 1.85, 100.00, 185.00, 'cashout', 140.00, 'Cashed out for profit', '2026-04-10 14:00:00', '2026-04-22 18:00:00');

-- Link bets with tags
INSERT INTO bet_tags (bet_id, tag_id) VALUES
(1, 1), -- Man Utd - High Confidence
(1, 2), -- Man Utd - Value Bet
(2, 4), -- Chelsea - Risky
(3, 1), -- Lakers - High Confidence
(4, 4), -- Accum - Risky
(5, 1), -- Real Madrid - High Confidence
(6, 1), -- Man City - High Confidence
(7, 4), -- Warriors - Risky
(8, 2), -- Milan - Value Bet
(9, 1), -- Maple Leafs - High Confidence
(10, 2), -- Yankees - Value Bet
(11, 4), -- Everton - Risky
(12, 3); -- Southampton - Live Bet

-- Link bets with tipsters
INSERT INTO bet_tipsters (bet_id, tipster_id) VALUES
(1, 1), -- Man Utd - Football Expert
(5, 1), -- Real Madrid - Football Expert
(6, 1), -- Man City - Football Expert
(2, 2), -- Chelsea - Stats Analyst
(3, 2), -- Lakers - Stats Analyst
(4, 2), -- Accum - Stats Analyst
(7, 1), -- Warriors - Football Expert
(12, 3); -- Southampton - In-Play Specialist

-- Insert bankroll snapshots
INSERT INTO bankroll_snapshots (user_id, balance, snapshot_date) VALUES
(@user_id, 1000.00, '2026-02-28'),
(@user_id, 1050.00, '2026-03-01'),
(@user_id, 1185.00, '2026-03-16'),
(@user_id, 1380.00, '2026-03-21'),
(@user_id, 1330.00, '2026-04-02'),
(@user_id, 1742.50, '2026-04-06'),
(@user_id, 1642.50, '2026-04-11'),
(@user_id, 1782.50, '2026-04-22');

-- Insert bankroll adjustments
INSERT INTO bankroll_adjustments (user_id, bookmaker_id, type, amount, notes, created_at) VALUES
(@user_id, 1, 'deposit', 200.00, 'Monthly deposit', '2026-03-01 10:00:00'),
(@user_id, 2, 'bonus', 25.00, 'Signup bonus', '2026-03-05 14:30:00'),
(@user_id, 4, 'deposit', 500.00, 'Welcome bonus account', '2026-03-10 09:00:00'),
(@user_id, 1, 'withdrawal', 100.00, 'Profit withdrawal', '2026-04-01 16:00:00');

COMMIT;
