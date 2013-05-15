
DROP TABLE if exists `filepermission`;
CREATE TABLE `filepermission` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `fileid` int(11) unsigned DEFAULT NULL,
  `folderid` int(11) unsigned DEFAULT NULL,
  `accessflag` tinyint(4) unsigned DEFAULT '0',
  `allowedlist` varchar(500) DEFAULT NULL,
  `deniedlist` varchar(500) DEFAULT NULL,
  `createdby` int(11) unsigned NOT NULL,
  `datecreated` datetime NOT NULL,
  `lastupdatedby` int(11) unsigned DEFAULT NULL,
  `lastupdatedate` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_folder_createdby` (`createdby`) USING BTREE,
  KEY `fk_folder_lastupdatedby` (`lastupdatedby`) USING BTREE,
  KEY `fk_filepermission_fileid` (`fileid`),
  KEY `fk_filepermission_folderid` (`folderid`),
  CONSTRAINT `fk_filepermission_createdby` FOREIGN KEY (`createdby`) REFERENCES `useraccount` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_filepermission_fileid` FOREIGN KEY (`fileid`) REFERENCES `useraccount` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_filepermission_folderid` FOREIGN KEY (`folderid`) REFERENCES `useraccount` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_filepermission_lastupdatedby` FOREIGN KEY (`lastupdatedby`) REFERENCES `useraccount` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
DROP TABLE if exists `filepermission`;

/* Alter table in target */
ALTER TABLE `corporatefile` 
	ADD COLUMN `accessflag` tinyint(4) unsigned   NULL DEFAULT '0' after `lastupdatedate`, 
	ADD COLUMN `allowedlist` varchar(500)  COLLATE utf8_general_ci NULL after `accessflag`, 
	ADD COLUMN `deniedlist` varchar(500)  COLLATE utf8_general_ci NULL after `allowedlist`, COMMENT='';

/* Alter table in target */
ALTER TABLE `folder` 
	ADD COLUMN `accessflag` tinyint(4) unsigned   NULL DEFAULT '0' after `lastupdatedate`, 
	ADD COLUMN `allowedlist` varchar(500)  COLLATE utf8_general_ci NULL after `accessflag`, 
	ADD COLUMN `deniedlist` varchar(500)  COLLATE utf8_general_ci NULL after `allowedlist`, COMMENT='';