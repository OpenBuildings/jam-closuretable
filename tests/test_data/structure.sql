DROP TABLE IF EXISTS `test_closurelists`;
CREATE TABLE `test_closurelists` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `test_closurelists_branches`;
CREATE TABLE `test_closurelists_branches` (
  `ansestor_id` int(11) UNSIGNED NOT NULL,
  `descendant_id` int(11) UNSIGNED NOT NULL,
  `depth` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `test_closurelists` (`id`, `name`)
VALUES
	(1,'One'),
	(2,'Two'),
	(3,'Three'),
	(4,'Four'),
	(5,'Five'),
	(6,'Six'),
	(7,'Seven'),
	(10,'Ten'),
	(11,'Eleven');

INSERT INTO `test_closurelists_branches` (`ansestor_id`, `descendant_id`, `depth`)
VALUES
	(1,1,0),
	(2,2,0),
	(3,3,0),
	(2,3,1),
	(4,4,0),
	(5,5,0),
	(6,6,0),
	(4,5,1),
	(4,6,1),
	(1,2,1),
	(1,3,2),
	(1,4,1),
	(1,5,2),
	(1,6,2),
	(7,7,0),
	(10,10,0),
	(11,11,0),
	(10,11,1);