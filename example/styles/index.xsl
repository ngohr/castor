<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:output method="html"
    omit-xml-declaration="yes"
    encoding="UTF-8"
    indent="yes"
	doctype-system="about:legacy-compat"
/>

<xsl:template match="/">
<html>
	<xsl:attribute name="lang">de</xsl:attribute>
	<head>
		<title>Startpage</title>
	</head>
	<body>
		<xsl:value-of select="myelement" />
	</body>
</html>
</xsl:template>

</xsl:stylesheet>