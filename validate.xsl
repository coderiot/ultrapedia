<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:template match="log">
	<html>
	  <body>
		<ul>
		  <xsl:for-each select="record">
			<li><xsl:value-of select="message"/></li>
		  </xsl:for-each>
		</ul>
	  </body>
	</html>
  </xsl:template>
</xsl:stylesheet>