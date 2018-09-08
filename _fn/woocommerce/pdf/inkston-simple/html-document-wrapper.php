<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title><?php echo $this->get_title(); ?></title>
	<style type="text/css">
/* Load font */
@font-face {
    font-family: 'ukai';
    font-style: normal;
    font-weight: bold;
    src: local( 'ukai'), local( 'ukai'), url(<?php echo (ABSPATH); ?>fonts/ukai.ttf) format( 'truetype');
}
			.tax_label{display:none;}

    </style>	<style type="text/css"><?php $this->template_styles(); ?></style>
	<style type="text/css"><?php do_action( 'wpo_wcpdf_custom_styles', $this->get_type(), $this ); ?></style>
</head>
<body class="<?php echo $this->get_type(); ?>">
<?php echo $content; ?>
</body>
</html>