CREATE TABLE `foo`
(
  `foo` varchar(10) NOT NULL,
  `bar` int(11) NOT NULL,
  `foobar` float NOT NULL,
  `blah` varchar(20) NOT NULL
);

INSERT INTO `foo` (`foo`, `bar`, `foobar`, `blah`) VALUES
('asdf', 0, 10, 'asdf'),
('Andrew', 0, 10.3, 'Test 1'),
('Testing', 12, 12.5, 'Test 2');