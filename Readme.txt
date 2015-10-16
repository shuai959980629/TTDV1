--------------图腾贷目录结构
|
|---doc 项目文档和数据库文件
|---ui 项目UI设计前端
|  |--html
|  |   |--MOBILE-HTML:移动端页面
|  |   |--WEB-HTML :WEB页面
|  |--原型图
|  |--设计图
|  |--素材
|  |--psd。UI界面
|
|---web 项目核心文件
|  |--com_party 公共业务逻辑
|  |   |--helper 公共辅助函数
|  |   |--libraries 公共类库
|  |   |--models 业务逻辑model
|
|  |--framework CI框架
|  |   |--core CI核心类
|  |   |--database CI数据库处理
|  |   |--fonts
|  |   |--helpers
|  |   |--language
|  |   |--libraries
|
|  |--manage 项目管理后台
|  |   |--application 项目应用目录
|  |   |  |--core 项目控制器最高基类
|  |   |  |--controllers 控制器
|  |   |  |--view 视图层
|  |   |  |--config 配置文件
|  |   |  |  |--privilege.php权限配置文件
|  |   |  |  |--role.php 角色配置文件
|  |   |--assets 静态资源文件
|  |   |  |--css
|  |   |  |--images
|  |   |  |--js
|  |   |  |--plugins 存放独立的js插件相关文件
|  |   |--data 用来存放，上传的图片或者是验证码文件之类的
|  |   |--index.php项目入口
|
|  |--mobile 移动端项目文件
|  |   |--application 项目应用目录
|  |   |  |--core 项目控制器最高基类
|  |   |  |--controllers 控制器
|  |   |  |--view 视图层
|  |   |  |--config 配置文件
|  |   |  |  |--privilege.php权限配置文件
|  |   |  |  |--role.php 角色配置文件
|  |   |--assets 静态资源文件
|  |   |  |--css
|  |   |  |--images
|  |   |  |--js
|  |   |  |--plugins 存放独立的js插件相关文件
|  |   |--data 用来存放，上传的图片或者是验证码文件之类的
|  |   |--index.php
|
|  |--www PC端web项目文件
|  |   |--application 项目应用目录
|  |   |  |--core 项目控制器最高基类
|  |   |  |--controllers 控制器
|  |   |  |--view 视图层
|  |   |  |--config 配置文件
|  |   |  |  |--privilege.php权限配置文件
|  |   |  |  |--role.php 角色配置文件
|  |   |--assets 静态资源文件
|  |   |  |--css
|  |   |  |--images
|  |   |  |--js
|  |   |  |--plugins 存放独立的js插件相关文件
|  |   |--data 用来存放，上传的图片或者是验证码文件之类的
|  |   |--index.php项目入口
|
|
|
|

////=======================开发任务===============================================================================================================================================================================

图腾贷老系统后台 充值管理
http://old.tt1.com.cn/?46547ac9bbc71df6 用户名：admin 密码：123456

任务一：

3 充值管理

3.1 查看用户充值记录，包括：用户账号、订单号、充值渠道、充值金额、充值手续费 实际到账金额、当前状态、操作时间（最近一次）

3.2 支持按用户账号、状态、时间段、单号查询

3.3 提供手动补单功能，并记录补单操作日志，
3.4 提供导出功能(暂时不处理)


#CREATE TABLE tt1_p2p.t_recharge_order_1 LIKE tt1_p2p.t_recharge_order_0

SELECT `t_recharge_order_1`.* FROM t_recharge_order_1 WHERE `t_recharge_order_1`.`id` <= 50 ORDER BY `t_recharge_order_1`.`id` desc LIMIT 50


-- ----------------------------
-- Procedure structure for insertdata
-- ----------------------------

DROP PROCEDURE if exists insertdata;
DELIMITER ;;
CREATE PROCEDURE insertdata()
begin
DECLARE id INT DEFAULT 1;
DECLARE order_no INT DEFAULT 10000000 ;
WHILE id<1000
DO
insert into t_recharge_order_0(id,order_no,uid,`status`,amount,fee,money,payment_code,paid_time) VALUES (id,order_no,1,'wait',1000,0,1000,'宝付','1431502273');
set id = id+1;
set order_no = order_no+9999;
END WHILE ;
END;;
call insertdata();




任务二：

数据库：t_tender_log_0; t_repay_log_0;

原型：file:///D:/wamp/www/tt1v2/ui/%E5%8E%9F%E5%9E%8B%E5%9B%BE/%E6%88%91%E7%9A%84%E6%8A%95%E8%B5%84.html

投标相关

自动投标设置

投标记录

待收列表/已收列表

债权转让

借款列表、详情、投标


下一步是：手机验证码，密码，确认密码。
（如果他填的手机号已经注册了，那么下一步就只需要填一个验证码，然后按钮就是绑定账号）
再下一步，就是认证：真实姓名，身份证号，按钮名字叫：申请认证；最下面两个字：跳过


图腾贷加息券使用规则

此次加息券仅限于图腾贷周年庆活动暨配合降息使用；
1.	获得加息券的入口为：微信端签到页面。每天有一次获得机会，共三次；
2.	加息券固定利率为3%；
3.	3天后所有未抢过的注册用户均可获得一张3%的加息券；
4.	加息券使用期限为：30天（以抢到之日开始计算）；过期作废；
5.	加息券可以用于手动投资，自动投资；
6.	加息券只能投资质押标（也可以投资债权转让标）；
7.	使用了加息券的标，如果出现提前截标，剩下的部分不会做额外补偿（按照现有补偿标准进行补偿）；

附：微信签到规则：
1.	每人每天有一次签到机会；
2.	签到按时间累计，例：第一天签到得1图腾币；第二天得2图腾币；第三天得3图腾币，按自然月进行累计，
    最多得31图腾币。如果中途任何一天中断，那么下次签到将会从1图腾币开始计算；
3.	签到按自然月进行计算，每月一号，从一个图腾币开始计算；





