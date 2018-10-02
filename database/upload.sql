
--
-- 表的结构 `upload`
--

CREATE TABLE `upload` (
  `id` int(11) NOT NULL,
  `real_name` varchar(255) NOT NULL COMMENT '原始文件名称',
  `file_name` varchar(255) DEFAULT NULL COMMENT '文件路径',
  `thumb_name` varchar(255) DEFAULT NULL COMMENT '缩略图路径',
  `file_ext` varchar(255) DEFAULT NULL COMMENT '扩展名',
  `file_mime` varchar(255) DEFAULT NULL COMMENT 'MIME类型',
  `file_size` int(11) DEFAULT '0' COMMENT '文件大小',
  `md5` text COMMENT 'MD5',
  `sha1` text COMMENT 'SHA1',
  `down_hits` int(11) DEFAULT '0' COMMENT '下载次数',
  `created_by` int(11) NOT NULL COMMENT '创建者',
  `updated_by` int(11) NOT NULL COMMENT '更新者',
  `created_at` int(11) NOT NULL COMMENT '添加时间',
  `updated_at` int(11) NOT NULL COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for table `upload`
--
ALTER TABLE `upload`
  ADD PRIMARY KEY (`id`);

--
-- 使用表AUTO_INCREMENT `upload`
--
ALTER TABLE `upload`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
