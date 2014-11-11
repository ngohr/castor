<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:template match="/">
	<page>
		<xsl:if test="//sitemap/page[@name = //castor/pagename]/@index">
			<xsl:attribute name="index"><xsl:value-of select="//sitemap/page[@name = //castor/pagename]/@index" /></xsl:attribute>
		</xsl:if>
		<xsl:attribute name="return">
			<xsl:choose>
				<xsl:when test="not(//sitemap/page[@name = //castor/pagename]/@return)">
					DomDocument
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="//sitemap/page[@name = //castor/pagename]/@return" />
				</xsl:otherwise>
			</xsl:choose>
		</xsl:attribute>
		<title><xsl:value-of select="//sitemap/page[@name = //castor/pagename]/title" /></title>
		<xsl:apply-templates select="//sitemap/page[@name = //castor/pagename]/expand" />
		<xsl:apply-templates select="//sitemap/page[@name = //castor/pagename]/constant" />
		<xsl:apply-templates select="//sitemap/page[@name = //castor/pagename]/arr" />
		<xsl:apply-templates select="//sitemap/page[@name = //castor/pagename]/file" />
	</page>
	<xsl:choose>
		<xsl:when test="not(//castor/actionname)">			
			<xsl:apply-templates select="//sitemap/page[@name = //castor/pagename]/action" />
		</xsl:when>
		<xsl:otherwise>
			<xsl:apply-templates select="//sitemap/page[@name = //castor/pagename]/action[@name = //castor/actionname]" />
		</xsl:otherwise>
	</xsl:choose>
</xsl:template>

<xsl:template match="action">
	<action>
		<xsl:attribute name="name">
			<xsl:value-of select="@name" />
		</xsl:attribute>
		<xsl:attribute name="return">
			<xsl:value-of select="@return" />
		</xsl:attribute>
		<xsl:attribute name="title">
			<xsl:value-of select="@title" />
		</xsl:attribute>
		<class>
			<xsl:choose>
				<xsl:when test="not(class)"><xsl:value-of select="../class" /></xsl:when>
				<xsl:otherwise><xsl:value-of select="class" /></xsl:otherwise>
			</xsl:choose>
		</class>
		<method>
			<xsl:choose>
				<xsl:when test="not(method)"><xsl:value-of select="../method" /></xsl:when>
				<xsl:otherwise><xsl:value-of select="method" /></xsl:otherwise>
			</xsl:choose>
		</method>
		<xsl:if test="file">
		<file><xsl:value-of select="file" /></file>
		</xsl:if>
		<style>
			<xsl:attribute name="renderby"><xsl:value-of select="style/@renderby" /></xsl:attribute>
			<xsl:value-of select="style" />
		</style>
		<xsl:apply-templates select="constant" />
		<xsl:apply-templates select="expand" />
		<xsl:apply-templates select="arr" />
	</action>
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

<xsl:template match="constant">
	<constant>
		<xsl:attribute name="name"><xsl:value-of select="@name" /></xsl:attribute>
		<xsl:value-of select="." />
	</constant>
</xsl:template>

<xsl:template match="file">
	<file><xsl:value-of select="." /></file>
</xsl:template>

<xsl:template match="expand">
	<expand node="frontFooter">
		<xsl:attribute name="node"><xsl:value-of select="@node" /></xsl:attribute>
		<xsl:for-each select="add">
		<add>
			<xsl:attribute name="name"><xsl:value-of select="@name" /></xsl:attribute>
			<xsl:value-of select="." />
		</add>
		</xsl:for-each>
	</expand>
</xsl:template>

</xsl:stylesheet>