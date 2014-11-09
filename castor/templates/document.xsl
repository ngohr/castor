<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:template match="/">
	<action>
		<return>
			<xsl:choose>
				<xsl:when test="not(//sitemap/page[@name = //castor/pagename]/action[@name = //castor/actionname]/@return)">
					<xsl:value-of select="//sitemap/page[@name = //castor/pagename]/@return" />
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="//sitemap/page[@name = //castor/pagename]/action[@name = //castor/actionname]/@return" />
				</xsl:otherwise>
			</xsl:choose>
		</return>
		<xsl:apply-templates select="//sitemap/page[@name = //castor/pagename]/action[@name = //castor/actionname]" />
		<xsl:apply-templates select="//sitemap/page[@name = //castor/pagename]/arr" />
	</action>
</xsl:template>

<xsl:template match="action">
	<xsl:choose>
		<xsl:when test="count(class) &gt; 0 and class != ''">
			<class><xsl:value-of select="class" /></class>
		</xsl:when>
		<xsl:otherwise>
			<class><xsl:value-of select="../class" /></class>
		</xsl:otherwise>
	</xsl:choose>
	<xsl:choose>
		<xsl:when test="count(method) &gt; 0 and method != ''">
			<method><xsl:value-of select="method" /></method>
		</xsl:when>
		<xsl:otherwise>
			<method><xsl:value-of select="../method" /></method>
		</xsl:otherwise>
	</xsl:choose>
	<xsl:choose>
		<xsl:when test="count(file) &gt; 0 and file != ''">
			<file><xsl:value-of select="file" /></file>
		</xsl:when>
		<xsl:otherwise>
			<file><xsl:value-of select="../file" /></file>
		</xsl:otherwise>
	</xsl:choose>
	<xsl:choose>
		<xsl:when test="count(style) &gt; 0 and style != ''">
			<style>
				<xsl:attribute name="renderby"><xsl:value-of select="style/@renderby" /></xsl:attribute>
				<xsl:value-of select="style" />
			</style>
		</xsl:when>
		<xsl:otherwise>
			<style>
				<xsl:attribute name="renderby"><xsl:value-of select="../style/@renderby" /></xsl:attribute>
				<xsl:value-of select="../style" />
			</style>
		</xsl:otherwise>
	</xsl:choose>
	<xsl:apply-templates select="style" />
	<xsl:apply-templates select="arr" />
</xsl:template>

<xsl:template match="arr">
	<arr>
		<xsl:attribute name="name"><xsl:value-of select="@name" /></xsl:attribute>
		<xsl:for-each select="var">
			<var>
				<xsl:attribute name="name"><xsl:value-of select="@name" /></xsl:attribute>
				<xsl:value-of select="." />
			</var>
		</xsl:for-each>
	</arr>
</xsl:template>

</xsl:stylesheet>