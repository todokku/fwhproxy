# FWHProxy

一个可部署在免费PHP主机空间的代理(~~盗链~~)脚本。

功能：生成一个针对目标服务器上的内容的代理URL，通过此URL可直接访问到目标内容，不会要求提供Referer/Cookie等信息。

**目前仅支持Pixiv。**

# Demo

* 单图: http://proxy.freevar.com/pixiv/65089776
* 图集中的一页: http://proxy.freevar.com/pixiv/74369969/3

上述Demo图片来自作者：[Hiten](https://www.pixiv.net/member.php?id=490219)，**如有侵权请告知，我会删除相关内容。**

> 你可通过更改URL中的插画ID，来查看其他图片。

# Installation

首先你需要注册一个免费的PHP主机空间。

这里推荐两个主机空间：

| 服务商 | 注册限制 | HTTPS | 流量 |
|---|---|---|---|
| [FreeWHA](https://freewha.com/) | 无 | 不支持 | 无限制 |
| [GoogieHost](https://www.googiehost.com/) | 人工审核 | 支持 | 无限制 |

> Demo使用的是FreeWHA提供的主机空间。

你也可以选择其他主机空间，但是需要满足如下条件：

* PHP: 7.1版本最佳，包含`curl`和`mysqli`函数库；
* MySQL: 版本不限；

准备好主机空间后，按照如下步骤进行安装：

1. 下载整个项目。
2. 将include目录下的`config.inc.example.php`改名为`config.inc.php`，并修改其中的mysql相关配置；
3. 将项目文件上传到主机空间的Web根目录；
4. 在浏览器中访问地址： `http://your-host-name/install.php?username=<Pixiv用户名>&password=<Pixiv密码>` ，进行授权
5. 为安全起见，建议在饿授权后删除服务器上的`install.php`文件；
6. Enjoy it!

如果没有Pixiv帐号，可将`config.inc.php`中的`_PIXIV_NEED_OAUTH`设置为`false`，就可以跳过第4步的授权过程。

在不授权的情况下，只可以访问Pixiv的公开内容，因此无法访问R-18内容的图片。

# Usage

你可以通过如下地址，访问Pixiv上指定的图片：

* 访问单张图片: `http://your-host-name/pixiv/<illust_id>`
* 访问单张图片的指定尺寸: `http://your-host-name/pixiv/<illust_id>/[medium|large]`
* 访问图集中某张图片: `http://your-host-name/pixiv/<illust_id>/<page>`
* 访问图集中某张图片的指定尺寸: `http://your-host-name/pixiv/<illust_id>/<page>/[medium|large]`

如果上述URL不起作用，可能是你的主机空间不支持.htaccess，那么可以使用如下URL：

`http://your-host-name/pixiv.php?illust_id=<illust_id>[&page=<page>][&size=medium|large]`

# License

MIT
