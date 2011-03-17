CREATE TABLE `ssearch_content` (
  `uid` varchar(32) NOT NULL,
  `mime_type` varchar(64) NOT NULL,
  `title` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `protocol` varchar(10) NOT NULL,
  `domain` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`uid`),
  KEY `date` (`date`),
  KEY `domain` (`domain`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
