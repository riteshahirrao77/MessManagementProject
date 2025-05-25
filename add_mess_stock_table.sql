-- Create mess_stock table
CREATE TABLE IF NOT EXISTS `mess_stock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_name` varchar(100) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit` varchar(20) NOT NULL,
  `price_per_unit` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add some sample data
INSERT INTO `mess_stock` (`item_name`, `quantity`, `unit`, `price_per_unit`) VALUES
('Rice', 100.00, 'kg', 40.00),
('Wheat Flour', 50.00, 'kg', 35.00),
('Cooking Oil', 20.00, 'l', 120.00),
('Onions', 30.00, 'kg', 25.00),
('Tomatoes', 20.00, 'kg', 30.00),
('Potatoes', 40.00, 'kg', 20.00),
('Salt', 10.00, 'kg', 15.00),
('Sugar', 15.00, 'kg', 45.00),
('Tea Leaves', 5.00, 'kg', 200.00),
('Milk', 20.00, 'l', 60.00); 