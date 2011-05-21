--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `id` int(11) NOT NULL auto_increment,
  `username` varchar(24) NOT NULL,
  `salt1` varchar(32) NOT NULL,
  `salt2` varchar(32) NOT NULL,
  `salt3` varchar(32) NOT NULL,
  `hash1` varchar(32) NOT NULL,
  `hash23` varchar(32) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `username` (`username`)
);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(32) NOT NULL,
  `account` int(11) NOT NULL,
  `ip` varchar(15) NOT NULL,
  `expire` datetime NOT NULL,
  `challenge` varchar(32) NOT NULL,
  `sessionAuthHash` varchar(32) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `account` (`account`)
);

-- --------------------------------------------------------

--
-- Table structure for table `usedNonces`
--

CREATE TABLE `usedNonces` (
  `session_id` varchar(32) NOT NULL,
  `nonce` int(11) NOT NULL,
  `delete` tinyint(1) NOT NULL,
  PRIMARY KEY  (`session_id`,`nonce`)
);
