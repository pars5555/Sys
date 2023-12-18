<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <meta name="viewport" content="initial-scale=1.0,width=device-width">
        <link href="/out/{SUB_DOMAIN_DIR_FILE_NAME}/sys.css?{$sys_config.VERSION}" type="text/css" rel="stylesheet">
        <script type="text/javascript" src="/out/{SUB_DOMAIN_DIR_FILE_NAME}/sys.js?{$sys_config.VERSION}"></script>
        <title>Title</title>
    </head>
    <body>
        {include file="$VIEWS_DIR/main/header.tpl"}
        <section id="main">
            {include file="$included_in_index"}
        </section>
        {include file="$VIEWS_DIR/main/footer.tpl"}
    </body>
</html>