<?php
header('Content-Type: text/html; charset=utf-8');

$theme = $this->get('theme');

$res = Resource::getInstance();
$res->addJs('/resources/x-master-theme/js/Overrides.js', -3);
$res->addJs('/js/app/system/common.js', -2);
$res->addJs('/resources/x-master-theme/js/Application.js', -1);

$res->addJs('/js/lang/' . $this->get('lang') . '.js', -1000, true);

if ($this->get('development'))
    $res->addJs('/js/lib/extjs/build/ext-all-debug.js', 2, true, 'head');
else
    $res->addJs('/js/lib/extjs/build/ext-all.js', 2, true, 'head');

$res->addJs('/resources/x-master-theme/theme-master.js', 3, true, 'head');

$res->addJs('/js/lib/extjs/build/classic/locale/locale-' . $this->get('lang') . '.js', 4, true, 'head');

$res->addInlineJs('var developmentMode = ' . intval($this->get('development')) . ';');

$res->addCss('/resources/x-master-theme/resources/theme-master-all.css', 100);
$res->addCss('/css/system/style.css', 2);
$res->addCss('/resources/x-master-theme/css/style.css', 3);


$token = '';
if ($this->get('useCSRFToken')) {
    $csrf = new Security_Csrf();
    $token = $csrf->createToken();
}

$wwwRoot = Request::wwwRoot();

$request = \Dvelum\Request::factory();
$lang = \Dvelum\Lang::lang();
$user = \Dvelum\App\Session\User::factory();
$moduleAcl = $user->getModuleAcl();

$menuData = [];
$modules = $this->modules;
$iconsStylesGenerator = "";
foreach ($modules as $data) {
    if (!$data['active'] || !$data['in_menu'] || !isset($this->userModules[$data['id']])) {
        continue;
    }
    $menuData[] = [
        'id' => $data['id'],
        'dev' => $data['dev'],
        'url' => $request->url([$this->get('adminPath'), $data['id']]),
        'title' => $data['title'],
        'text' => $data['title'],
        'iconCls' => $data['id'] . "_icon",
        'leaf' => true
    ];
    $iconsStylesGenerator .= "Ext.util.CSS.createStyleSheet('." . $data['id'] . "_icon {background-image: url(" . $wwwRoot . $data['icon'] . ") !important; background-size: 21px 21px !important; height: 21px;top: 7px;}');";
}
$menuData[] = [
    'id' => 'logout',
    'dev' => false,
    'url' => $request->url([$this->get('adminPath'), '']) . '?logout=1',
    'title' => $lang->get('LOGOUT'),
    'icon' => $wwwRoot . 'i/system/icons/logout.png'
];

$res->addInlineJs('
		app.menuData = ' . json_encode($menuData) . ';
		app.permissions = Ext.create("app.PermissionsStorage");
		var rights = ' . json_encode($moduleAcl->getPermissions()) . ';
		app.permissions.setData(rights);
                ' . $iconsStylesGenerator);
?>
<!DOCTYPE html>
<html>
    <head>
        <?php /* <BASE href="<?php echo Request::baseUrl();?>"> */ ?>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
        <?php
        if ($this->get('useCSRFToken'))
            echo '<meta name="csrf-token" content="' . $token . '"/>';
        ?>
        <title><?php echo $this->get('page')->title; ?>  .:: BACK OFFICE PANEL ::.  </title>
        <link rel="shortcut icon" href="<?php echo $wwwRoot; ?>i/favicon.png" />
        <?php
        echo $res->includeCss(true);
        echo $res->includeJsByTag(true, false, 'head');
        ?>
    </head>
    <body>
        <div id="header" class="x-hidden">
            <div class="sysVersion"><img src="<?php echo $wwwRoot; ?>i/logo-s.png" />
                <span class="num"><?php echo $this->get('version'); ?></span>
                <div class="loginInfo"><?php echo $lang->get('YOU_LOGGED_AS'); ?>:
                    <span class="name"><?php echo $user->name; ?></span>
                    <span class="logout"><a href="<?php echo $request->url([$this->get('adminPath'), '']); ?>?logout=1">
                            <img src="<?php echo $wwwRoot; ?>i/system/icons/logout.png" title="<?php echo $lang->get('LOGOUT'); ?>" height="16" width="16">
                        </a></span>
                </div>
            </div>
        </div>
        <?php
        echo $res->includeJsByTag(true, false, 'external');
        echo $res->includeJs(true, true);
        ?>
    </body>
</html>
