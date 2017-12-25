-- -----------------------------
-- 导出时间 `2017-03-13 18:55:29`
-- -----------------------------
DROP TABLE IF EXISTS `tb_web_cate`;
DROP TABLE IF EXISTS `tb_web_content`;
DROP TABLE IF EXISTS `tb_web_content_article`;
DROP TABLE IF EXISTS `tb_web_content_product`;
DROP TABLE IF EXISTS `tb_web_content_video`;
DROP TABLE IF EXISTS `tb_web_field`;
DROP TABLE IF EXISTS `tb_web_goods`;
DROP TABLE IF EXISTS `tb_web_model`;
-- -----------------------------
-- 删除 `web` 模块相关菜单
-- -----------------------------
DELETE FROM tb_auth_rule WHERE module='web'