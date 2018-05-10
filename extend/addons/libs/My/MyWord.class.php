<?php
class MyWord
{
    function start($data='')
    {
        ob_start();
        echo '<html xmlns:v="urn:schemas-microsoft-com:vml"
                xmlns:o="urn:schemas-microsoft-com:office:office"
                xmlns:w="urn:schemas-microsoft-com:office:word"
                xmlns="http://www.w3.org/TR/REC-html40">
                <head>
                <!--[if gte mso 9]><xml>
                <o:DocumentProperties>
                
                <o:Author>MC SYSTEM</o:Author>
                <o:LastAuthor>MC SYSTEM</o:LastAuthor>
                <o:Revision>2</o:Revision>
                <o:TotalTime>1</o:TotalTime>
                <o:Created>2012-05-31T14:42:00Z</o:Created>
                <o:LastSaved>2012-05-31T14:42:00Z</o:LastSaved>
                <o:Pages>1</o:Pages>
                <o:Characters>5</o:Characters>
                <o:Company>MC SYSTEM</o:Company>
                <o:Lines>1</o:Lines>
                <o:Paragraphs>1</o:Paragraphs>
                <o:CharactersWithSpaces>5</o:CharactersWithSpaces>
                <o:Version>11.5606</o:Version>
                </o:DocumentProperties>
                </xml><![endif]--><!--[if gte mso 9]><xml>
                <w:WordDocument>
                <w:View>Print</w:View>
                <w:SpellingState>Clean</w:SpellingState>
                <w:GrammarState>Clean</w:GrammarState>
                <w:PunctuationKerning/>
                <w:DrawingGridVerticalSpacing>7.8 磅</w:DrawingGridVerticalSpacing>
                <w:DisplayHorizontalDrawingGridEvery>0</w:DisplayHorizontalDrawingGridEvery>
                <w:DisplayVerticalDrawingGridEvery>2</w:DisplayVerticalDrawingGridEvery>
                <w:ValidateAgainstSchemas/>
                <w:SaveIfXMLInvalid>false</w:SaveIfXMLInvalid>
                <w:IgnoreMixedContent>false</w:IgnoreMixedContent>
                <w:AlwaysShowPlaceholderText>false</w:AlwaysShowPlaceholderText>
                <w:Compatibility>
                <w:SpaceForUL/>
                <w:BalanceSingleByteDoubleByteWidth/>
                <w:DoNotLeaveBackslashAlone/>
                <w:ULTrailSpace/>
                <w:DoNotExpandShiftReturn/>
                <w:AdjustLineHeightInTable/>
                <w:BreakWrappedTables/>
                <w:SnapToGridInCell/>
                <w:WrapTextWithPunct/>
                <w:UseAsianBreakRules/>
                <w:DontGrowAutofit/>
                <w:UseFELayout/>
                </w:Compatibility>
                <w:BrowserLevel>MicrosoftInternetExplorer4</w:BrowserLevel>
                </w:WordDocument>
                </xml><![endif]--><!--[if gte mso 9]><xml>
                <w:LatentStyles DefLockedState="false" LatentStyleCount="156">
                </w:LatentStyles>
                </xml><![endif]-->
                </head>
                <body>';
        echo $data;
    }
    function save($name)
    {
        echo "</body>
              </html>";
        $data = ob_get_contents();
        ob_end_clean();
        header("Content-Type:application/msword");
        header("Content-Disposition:attachment;filename=".$name.".doc");
        header("Pragma:no-cache");
        header("Expires:0");
        echo $data;
    }
}