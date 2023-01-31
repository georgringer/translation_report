#
# Table structure for table 'sys_redirect'
#
CREATE TABLE tx_translation_report_item (
	package varchar(255) DEFAULT '' NOT NULL,
	path varchar(2048) DEFAULT '' NOT NULL,
	translation_key varchar(255) DEFAULT '' NOT NULL,
	translation_default mediumtext
);
