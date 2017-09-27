<?php

/**

 cPdfWriter 0.2.1 (http://projects.palos.ro/cPdfWriter)

 A complete PHP5-based library for writing PDF documents based on
 FPDF(http://www.fpdf.org) and a few other scripts.

 Copyright (C)2005 Valeriu Palos (valeriu@palos.ro)
 2-4 Cristian Popisteanu Street,
 District 1, Bucharest, Romania.

 ============================================================================

 GNU LESSER GENERAL PUBLIC LICENSE
 This library is free software; you can redistribute it and/or
 modify it under the terms of the GNU Lesser General Public
 License as published by the Free Software Foundation; either
 version 2.1 of the License, or (at your option) any later version.

 This library is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 Lesser General Public License for more details.

 You should have received a copy of the GNU Lesser General Public
 License along with this library; if not, write to the Free Software
 Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

define('CPDFWRITER_PRODUCER','cPdfWriter 0.2.1 (http://projects.palos.ro/cPdfWriter)');

if(class_exists('cPdfWriter'))
    return;

class cPdfWriter
{
    #private
    protected $extgstates;
    protected $page;
    protected $n;
    protected $offsets;
    protected $buffer;
    protected $pages;
    protected $state;
    protected $compress;
    protected $DefOrientation;
    protected $CurOrientation;
    protected $OrientationChanges;
    protected $k;
    protected $fwPt;
    protected $fhPt;
    protected $fw;
    protected $fh;
    protected $wPt;
    protected $hPt;
    protected $w;
    protected $h;
    protected $lMargin;
    protected $tMargin;
    protected $rMargin;
    protected $bMargin;
    protected $cMargin;
    protected $x;
    protected $y;
    protected $lasth;
    protected $LineWidth;
    protected $CoreFonts;
    protected $fonts;
    protected $FontFiles;
    protected $diffs;
    protected $images;
    protected $PageLinks;
    protected $links;
    protected $FontFamily;
    protected $FontStyle;
    protected $FontAngle;
    protected $underline;
    protected $CurrentFont;
    protected $FontSizePt;
    protected $FontSize;
    protected $DrawColor;
    protected $FillColor;
    protected $TextColor;
    protected $ColorFlag;
    protected $ws;
    protected $AutoPageBreak;
    protected $PageBreakTrigger;
    protected $InFooter;
    protected $ZoomMode;
    protected $LayoutMode;
    protected $title;
    protected $subject;
    protected $author;
    protected $keywords;
    protected $creator;
    protected $AliasNbPages;
    protected $img_rb_x;
    protected $img_rb_y;
    protected $imgscale = 1;
    protected $PDFVersion = "1.4";
    protected $fillcomplex=false;
    protected $gradients;
    protected $Dashes=0;
    protected $Alpha=0;

    #protected
    protected function _dochecks()
    {
        if(1.1==1)
            $this->Error('Don\'t alter the locale before including class file');
        if(sprintf('%.1f',1.0)!='1.0')
            setlocale(LC_NUMERIC,'C');
    }

    protected function _getfontpath()
    {
        if(!defined('CPDFWRITER_FONTPATH') AND is_dir(dirname(__FILE__).'/data-fonts'))
            define('CPDFWRITER_FONTPATH', dirname(__FILE__).'/data-fonts/');
        return defined('CPDFWRITER_FONTPATH') ? CPDFWRITER_FONTPATH : '';
    }

    protected function _begindoc()
    {
        $this->state=1;
        $this->_out('%PDF-1.4');
    }

    protected function _putpages()
    {
        $nb=$this->page;
        if(!empty($this->AliasNbPages))
        {
            for($n=1;$n<=$nb;$n++)
                $this->pages[$n]=($this->compress) ? gzcompress(str_replace($this->AliasNbPages,$nb,gzuncompress($this->pages[$n]))) : str_replace($this->AliasNbPages,$nb,$this->pages[$n]) ;
        }
        if($this->DefOrientation=='P')
        {
            $wPt=$this->fwPt;
            $hPt=$this->fhPt;
        }
        else
        {
            $wPt=$this->fhPt;
            $hPt=$this->fwPt;
        }
        $filter=($this->compress) ? '/Filter /FlateDecode ' : '';
        for($n=1;$n<=$nb;$n++)
        {
            $this->_newobj();
            $this->_out('<</Type /Page');
            $this->_out('/Parent 1 0 R');
            if(isset($this->OrientationChanges[$n]))
                $this->_out(sprintf('/MediaBox [0 0 %.2f %.2f]',$hPt,$wPt));
            $this->_out('/Resources 2 0 R');
            if(isset($this->PageLinks[$n]))
            {

                $annots='/Annots [';
                foreach($this->PageLinks[$n] as $pl)
                {
                    $rect=sprintf('%.2f %.2f %.2f %.2f',$pl[0],$pl[1],$pl[0]+$pl[2],$pl[1]-$pl[3]);
                    $annots.='<</Type /Annot /Subtype /Link /Rect ['.$rect.'] /Border [0 0 0] ';
                    if(is_string($pl[4]))
                        $annots.='/A <</S /URI /URI '.$this->_textstring($pl[4]).'>>>>';
                    else
                    {
                        $l=$this->links[$pl[4]];
                        $h=isset($this->OrientationChanges[$l[0]]) ? $wPt : $hPt;
                        $annots.=sprintf('/Dest [%d 0 R /XYZ 0 %.2f null]>>',1+2*$l[0],$h-$l[1]*$this->k);
                    }
                }
                $this->_out($annots.']');
            }
            $this->_out('/Contents '.($this->n+1).' 0 R>>');
            $this->_out('endobj');


            $this->_newobj();
            $this->_out('<<'.$filter.'/Length '.strlen($this->pages[$n]).'>>');
            $this->_putstream($this->pages[$n]);
            $this->_out('endobj');
        }

        $this->offsets[1]=strlen($this->buffer);
        $this->_out('1 0 obj');
        $this->_out('<</Type /Pages');
        $kids='/Kids [';
        for($i=0;$i<$nb;$i++)
            $kids.=(3+2*$i).' 0 R ';
        $this->_out($kids.']');
        $this->_out('/Count '.$nb);
        $this->_out(sprintf('/MediaBox [0 0 %.2f %.2f]',$wPt,$hPt));
        $this->_out('>>');
        $this->_out('endobj');
    }

    protected function _putfonts()
    {
        $nf=$this->n;
        foreach($this->diffs as $diff)
        {
            $this->_newobj();
            $this->_out('<</Type /Encoding /BaseEncoding /WinAnsiEncoding /Differences ['.$diff.']>>');
            $this->_out('endobj');
        }
        $mqr=get_magic_quotes_runtime();
        set_magic_quotes_runtime(0);
        foreach($this->FontFiles as $file=>$info)
        {
            $this->_newobj();
            $this->FontFiles[$file]['n']=$this->n;
            $font='';
            $f=fopen($this->_getfontpath().$file,'rb',1);
            if(!$f)
                $this->Error('Font file not found');
            while(!feof($f))
                $font .= fread($f, 8192);
            fclose($f);
            $compressed=(substr($file,-2)=='.z');
            if(!$compressed && isset($info['length2']))
            {
                $header=(ord($font{0})==128);
                if($header)
                    $font=substr($font,6);
                if($header && ord($font{$info['length1']})==128)
                    $font=substr($font,0,$info['length1']).substr($font,$info['length1']+6);
            }
            $this->_out('<</Length '.strlen($font));
            if($compressed)
                $this->_out('/Filter /FlateDecode');
            $this->_out('/Length1 '.$info['length1']);
            if(isset($info['length2']))
                $this->_out('/Length2 '.$info['length2'].' /Length3 0');
            $this->_out('>>');
            $this->_putstream($font);
            $this->_out('endobj');
        }
        set_magic_quotes_runtime($mqr);
        foreach($this->fonts as $k=>$font)
        {
            $this->fonts[$k]['n']=$this->n+1;
            $type=$font['type'];
            $name=$font['name'];
            if($type=='core')
            {
                $this->_newobj();
                $this->_out('<</Type /Font');
                $this->_out('/BaseFont /'.$name);
                $this->_out('/Subtype /Type1');
                if($name!='Symbol' && $name!='ZapfDingbats')
                    $this->_out('/Encoding /WinAnsiEncoding');
                $this->_out('>>');
                $this->_out('endobj');
            }
            elseif($type=='Type1' || $type=='TrueType')
            {
                $this->_newobj();
                $this->_out('<</Type /Font');
                $this->_out('/BaseFont /'.$name);
                $this->_out('/Subtype /'.$type);
                $this->_out('/FirstChar 32 /LastChar 255');
                $this->_out('/Widths '.($this->n+1).' 0 R');
                $this->_out('/FontDescriptor '.($this->n+2).' 0 R');
                if($font['enc'])
                {
                    if(isset($font['diff']))
                        $this->_out('/Encoding '.($nf+$font['diff']).' 0 R');
                    else
                        $this->_out('/Encoding /WinAnsiEncoding');
                }
                $this->_out('>>');
                $this->_out('endobj');
                $this->_newobj();
                $cw=&$font['cw'];
                $s='[';
                for($i=32;$i<=255;$i++)
                    $s.=$cw[chr($i)].' ';
                $this->_out($s.']');
                $this->_out('endobj');
                $this->_newobj();
                $s='<</Type /FontDescriptor /FontName /'.$name;
                foreach($font['desc'] as $k=>$v)
                    $s.=' /'.$k.' '.$v;
                $file = $font['file'];
                if($file)
                    $s.=' /FontFile'.($type=='Type1' ? '' : '2').' '.$this->FontFiles[$file]['n'].' 0 R';
                $this->_out($s.'>>');
                $this->_out('endobj');
            }
            else
            {
                $mtd='_put'.strtolower($type);
                if(!method_exists($this, $mtd))
                    $this->Error('Unsupported font type: '.$type);
                $this->$mtd($font);
            }
        }
    }

    protected function _putimages()
    {
        $filter=($this->compress) ? '/Filter /FlateDecode ' : '';
        reset($this->images);
        while(list($file,$info)=each($this->images))
        {
            $this->_newobj();
            $this->images[$file]['n']=$this->n;
            $this->_out('<</Type /XObject');
            $this->_out('/Subtype /Image');
            $this->_out('/Width '.$info['w']);
            $this->_out('/Height '.$info['h']);
            if($info['cs']=='Indexed')
                $this->_out('/ColorSpace [/Indexed /DeviceRGB '.(strlen($info['pal'])/3-1).' '.($this->n+1).' 0 R]');
            else
            {
                $this->_out('/ColorSpace /'.$info['cs']);
                if($info['cs']=='DeviceCMYK')
                    $this->_out('/Decode [1 0 1 0 1 0 1 0]');
            }
            $this->_out('/BitsPerComponent '.$info['bpc']);
            if(isset($info['f']))
                $this->_out('/Filter /'.$info['f']);
            if(isset($info['parms']))
                $this->_out($info['parms']);
            if(isset($info['trns']) and is_array($info['trns']))
            {
                $trns='';
                for($i=0;$i<count($info['trns']);$i++)
                    $trns.=$info['trns'][$i].' '.$info['trns'][$i].' ';
                $this->_out('/Mask ['.$trns.']');
            }
            $this->_out('/Length '.strlen($info['data']).'>>');
            $this->_putstream($info['data']);
            unset($this->images[$file]['data']);
            $this->_out('endobj');
            if($info['cs']=='Indexed')
            {
                $this->_newobj();
                $pal=($this->compress) ? gzcompress($info['pal']) : $info['pal'];
                $this->_out('<<'.$filter.'/Length '.strlen($pal).'>>');
                $this->_putstream($pal);
                $this->_out('endobj');
            }
        }
    }

    protected function _putxobjectdict()
    {
        foreach($this->images as $image)
            $this->_out('/I'.$image['i'].' '.$image['n'].' 0 R');
    }

    protected function _putshaders()
    {
        foreach($this->gradients as $id=>$grad){
            $this->_newobj();
            $this->_out('<<');
            $this->_out('/FunctionType 2');
            $this->_out('/Domain [0.0 1.0]');
            $this->_out('/C0 ['.$grad['col1'].']');
            $this->_out('/C1 ['.$grad['col2'].']');
            $this->_out('/N 1');
            $this->_out('>>');
            $this->_out('endobj');
            $f1=$this->n;

            $this->_newobj();
            $this->_out('<<');
            $this->_out('/ShadingType '.$grad['type']);
            $this->_out('/ColorSpace /DeviceRGB');
            if($grad['type']==2)
            {
                $this->_out(sprintf('/Coords [%.3f %.3f %.3f %.3f]',$grad['coords'][0],$grad['coords'][1],$grad['coords'][2],$grad['coords'][3]));
                $this->_out('/Function '.$f1.' 0 R');
                $this->_out('/Extend [true true] ');
            }
            elseif($grad['type']==3)
            {
                $this->_out(sprintf('/Coords [%.3f %.3f 0 %.3f %.3f %.3f]',$grad['coords'][0],$grad['coords'][1],$grad['coords'][2],$grad['coords'][3],$grad['coords'][4]));
                $this->_out('/Function '.$f1.' 0 R');
                $this->_out('/Extend [true true] ');
            }
            $this->_out('>>');
            $this->_out('endobj');
            $this->gradients[$id]['id']=$this->n;
        }
    }

    protected function _fillGradient($params)
    {
        if(!is_array($params))
            return;
        $n=count($this->gradients)+1;
        switch($params[6])
        {
        case 'horizontal':
            $this->gradients[$n]['coords']=array(0,0,1,0);
            $this->gradients[$n]['type']=2;
            break;
        case 'vertical':
            $this->gradients[$n]['coords']=array(0,0,0,1);
            $this->gradients[$n]['type']=2;
            break;
        case 'diagonal_1':
            $this->gradients[$n]['coords']=array(0,1,1,0);
            $this->gradients[$n]['type']=2;
            break;
        case 'diagonal_2':
            $this->gradients[$n]['coords']=array(0,0,1,1);
            $this->gradients[$n]['type']=2;
            break;
        case 'horizontal_mirrored':
        case 'vertical_mirrored':
            $a=$params[0];
            $b=$params[1];
            $c=$params[2];
            $params[0]=$params[3];
            $params[1]=$params[4];
            $params[2]=$params[5];
            $params[3]=$a;
            $params[4]=$b;
            $params[5]=$c;
        default:
            $this->gradients[$n]['coords']=array(0.5,0.5,0.5,0.5,1);
            $this->gradients[$n]['type']=3;
            break;
        }
        $this->gradients[$n]['col1']=sprintf('%.3f %.3f %.3f',($params[0]/255),($params[1]/255),($params[2]/255));
        $this->gradients[$n]['col2']=sprintf('%.3f %.3f %.3f',($params[3]/255),($params[4]/255),($params[5]/255));
        $this->_out('/Sh'.$n.' sh');
    }

    protected function _fillImage($params,$x,$y,$w,$h)
    {
        $this->Image($params,$x,$y,$w,$h);
    }

    protected function _putresourcedict()
    {
        $this->_out('/ProcSet [/PDF /Text /ImageB /ImageC /ImageI]');
        $this->_out('/Font <<');
        foreach($this->fonts as $font)
            $this->_out('/F'.$font['i'].' '.$font['n'].' 0 R');
        $this->_out('>>');
        $this->_out('/XObject <<');
        $this->_putxobjectdict();
        $this->_out('>>');

        $this->_out('/ExtGState <<');
        foreach($this->extgstates as $k=>$extgstate)
            $this->_out('/GS'.$k.' '.$extgstate['n'].' 0 R');
        $this->_out('>>');

        $this->_out('/Shading <<');
        foreach($this->gradients as $id=>$grad)
            $this->_out('/Sh'.$id.' '.$grad['id'].' 0 R');
        $this->_out('>>');
    }

    protected function _putresources()
    {
        $this->_putshaders();

        for ($i = 1; $i <= count($this->extgstates); $i++)
        {
            $this->_newobj();
            $this->extgstates[$i]['n'] = $this->n;
            $this->_out('<</Type /ExtGState');
            foreach ($this->extgstates[$i]['parms'] as $k=>$v)
                $this->_out('/'.$k.' '.$v);
            $this->_out('>>');
            $this->_out('endobj');
        }

        $this->_putfonts();
        $this->_putimages();
        $this->offsets[2]=strlen($this->buffer);
        $this->_out('2 0 obj');
        $this->_out('<<');
        $this->_putresourcedict();
        $this->_out('>>');
        $this->_out('endobj');
    }

    protected function _putinfo()
    {
        $this->_out('/Producer '.$this->_textstring(CPDFWRITER_PRODUCER));
        if(!empty($this->title))
            $this->_out('/Title '.$this->_textstring($this->title));
        if(!empty($this->subject))
            $this->_out('/Subject '.$this->_textstring($this->subject));
        if(!empty($this->author))
            $this->_out('/Author '.$this->_textstring($this->author));
        if(!empty($this->keywords))
            $this->_out('/Keywords '.$this->_textstring($this->keywords));
        if(!empty($this->creator))
            $this->_out('/Creator '.$this->_textstring($this->creator));
        $this->_out('/CreationDate '.$this->_textstring('D:'.date('YmdHis')));
    }

    protected function _putcatalog()
    {
        $this->_out('/Type /Catalog');
        $this->_out('/Pages 1 0 R');
        if($this->ZoomMode=='fullpage')
            $this->_out('/OpenAction [3 0 R /Fit]');
        elseif($this->ZoomMode=='fullwidth')
            $this->_out('/OpenAction [3 0 R /FitH null]');
        elseif($this->ZoomMode=='real')
            $this->_out('/OpenAction [3 0 R /XYZ null null 1]');
        elseif(!is_string($this->ZoomMode))
            $this->_out('/OpenAction [3 0 R /XYZ null null '.($this->ZoomMode/100).']');
        if($this->LayoutMode=='single')
            $this->_out('/PageLayout /SinglePage');
        elseif($this->LayoutMode=='continuous')
            $this->_out('/PageLayout /OneColumn');
        elseif($this->LayoutMode=='two')
            $this->_out('/PageLayout /TwoColumnLeft');
    }

    protected function _puttrailer()
    {
        $this->_out('/Size '.($this->n+1));
        $this->_out('/Root '.$this->n.' 0 R');
        $this->_out('/Info '.($this->n-1).' 0 R');
    }

    function _putheader()
    {
        $this->_out('%PDF-'.$this->PDFVersion);
    }

    protected function _enddoc()
    {
        $this->_putheader();
        $this->_putpages();
        $this->_putresources();
        $this->_newobj();
        $this->_out('<<');
        $this->_putinfo();
        $this->_out('>>');
        $this->_out('endobj');
        $this->_newobj();
        $this->_out('<<');
        $this->_putcatalog();
        $this->_out('>>');
        $this->_out('endobj');
        $o=strlen($this->buffer);
        $this->_out('xref');
        $this->_out('0 '.($this->n+1));
        $this->_out('0000000000 65535 f ');
        for($i=1;$i<=$this->n;$i++)
            $this->_out(sprintf('%010d 00000 n ',$this->offsets[$i]));
        $this->_out('trailer');
        $this->_out('<<');
        $this->_puttrailer();
        $this->_out('>>');
        $this->_out('startxref');
        $this->_out($o);
        $this->_out('%%EOF');
        $this->state=3;
    }

    protected function _beginpage($orientation)
    {
        $this->page++;
        $this->pages[$this->page]='';
        $this->state=2;
        $this->x=$this->lMargin;
        $this->y=$this->tMargin;
        $this->FontFamily='';
        if(empty($orientation))
            $orientation=$this->DefOrientation;
        else
        {
            $orientation=strtoupper($orientation{0});
            if($orientation!=$this->DefOrientation)
                $this->OrientationChanges[$this->page]=true;
        }
        if($orientation!=$this->CurOrientation)
        {
            if($orientation=='P')
            {
                $this->wPt=$this->fwPt;
                $this->hPt=$this->fhPt;
                $this->w=$this->fw;
                $this->h=$this->fh;
            }
            else
            {
                $this->wPt=$this->fhPt;
                $this->hPt=$this->fwPt;
                $this->w=$this->fh;
                $this->h=$this->fw;
            }
            $this->PageBreakTrigger=$this->h-$this->bMargin;
            $this->CurOrientation=$orientation;
        }
    }

    protected function _endpage()
    {
        $this->pages[$this->page] = ($this->compress) ? gzcompress($this->pages[$this->page]) : $this->pages[$this->page];
        $this->state=1;
    }

    protected function _newobj()
    {
        $this->n++;
        $this->offsets[$this->n]=strlen($this->buffer);
        $this->_out($this->n.' 0 obj');
    }

    protected function _dounderline($x,$y,$txt)
    {
        $up = $this->CurrentFont['up'];
        $ut = $this->CurrentFont['ut'];
        $w = $this->_getStringIW($txt) + $this->ws * substr_count($txt,' ');
        return sprintf('%.2f %.2f %.2f %.2f re f', $x * $this->k, ($this->h - ($y - $up / 1000 * $this->FontSize)) * $this->k, $w * $this->k, -$ut / 1000 * $this->FontSizePt);
    }

    protected function _parsejpg($file)
    {
        $a=GetImageSize($file);
        if(empty($a))
            $this->Error('Missing or incorrect image file: '.$file);
        if($a[2]!=2)
            $this->Error('Not a JPEG file: '.$file);
        if(!isset($a['channels']) or $a['channels']==3)
            $colspace='DeviceRGB';
        elseif($a['channels']==4)
            $colspace='DeviceCMYK';
        else
            $colspace='DeviceGray';
        $bpc=isset($a['bits']) ? $a['bits'] : 8;
        $f=fopen($file,'rb');
        $data='';
        while(!feof($f))
            $data.=fread($f,4096);
        fclose($f);
        return array('w'=>$a[0],'h'=>$a[1],'cs'=>$colspace,'bpc'=>$bpc,'f'=>'DCTDecode','data'=>$data);
    }

    protected function _freadint($f)
    {
        $a=unpack('Ni',fread($f,4));
        return $a['i'];
    }

    protected function _readstr($var, &$pos, $n)
    {
        $string = substr($var, $pos, $n);
        $pos += $n;
        return $string;
    }

    protected function _readstr_int($var, &$pos)
    {
        $i=ord($this->_readstr($var, $pos, 1))<<24;
        $i+=ord($this->_readstr($var, $pos, 1))<<16;
        $i+=ord($this->_readstr($var, $pos, 1))<<8;
        $i+=ord($this->_readstr($var, $pos, 1));
        return $i;
    }

    protected function _parsemempng($var)
    {
        $pos=0;
        $sig = $this->_readstr($var,$pos, 8);
        if($sig != chr(137).'PNG'.chr(13).chr(10).chr(26).chr(10))
            $this->Error('Not a PNG image');
        $this->_readstr($var,$pos,4);
        $ihdr = $this->_readstr($var,$pos,4);
        if( $ihdr != 'IHDR')
            $this->Error('Incorrect PNG Image');
        $w=$this->_readstr_int($var,$pos);
        $h=$this->_readstr_int($var,$pos);
        $bpc=ord($this->_readstr($var,$pos,1));
        if($bpc>8)
            $this->Error('16-bit depth not supported: '.$file);
        $ct=ord($this->_readstr($var,$pos,1));
        if($ct==0)
            $colspace='DeviceGray';
        elseif($ct==2)
            $colspace='DeviceRGB';
        elseif($ct==3)
            $colspace='Indexed';
        else
            $this->Error('Alpha channel not supported: '.$file);
        if(ord($this->_readstr($var,$pos,1))!=0)
            $this->Error('Unknown compression method: '.$file);
        if(ord($this->_readstr($var,$pos,1))!=0)
            $this->Error('Unknown filter method: '.$file);
        if(ord($this->_readstr($var,$pos,1))!=0)
            $this->Error('Interlacing not supported: '.$file);
        $this->_readstr($var,$pos,4);
        $parms='/DecodeParms <</Predictor 15 /Colors '.($ct==2 ? 3 : 1).' /BitsPerComponent '.$bpc.' /Columns '.$w.'>>';
        $pal='';
        $trns='';
        $data='';
        do
        {
            $n=$this->_readstr_int($var,$pos);
            $type=$this->_readstr($var,$pos,4);
            if($type=='PLTE')
            {
                $pal=$this->_readstr($var,$pos,$n);
                $this->_readstr($var,$pos,4);
            }
            elseif($type=='tRNS')
            {
                $t=$this->_readstr($var,$pos,$n);
                if($ct==0)
                    $trns=array(ord(substr($t,1,1)));
                elseif($ct==2)
                    $trns=array(ord(substr($t,1,1)),ord(substr($t,3,1)),ord(substr($t,5,1)));
                else
                {
                    $pos=strpos($t,chr(0));
                    if(is_int($pos))
                        $trns=array($pos);
                }
                $this->_readstr($var,$pos,4);
            }
            elseif($type=='IDAT')
            {
                $data.=$this->_readstr($var,$pos,$n);
                $this->_readstr($var,$pos,4);
            }
            elseif($type=='IEND')
                break;
            else
                $this->_readstr($var,$pos,$n+4);
        }
        while($n);
        if($colspace=='Indexed' and empty($pal))
            $this->Error('Missing palette in '.$file);
        return array('w'=>$w,
                     'h'=>$h,
                     'cs'=>$colspace,
                     'bpc'=>$bpc,
                     'f'=>'FlateDecode',
                     'parms'=>$parms,
                     'pal'=>$pal,
                     'trns'=>$trns,
                     'data'=>$data);
    }

    public function _pngImage($data, $x, $y, $w=0, $h=0, $link='')
    {
        $id = md5($data);
        if(!isset($this->images[$id]))
        {
            $info = $this->_parsemempng( $data );
            $info['i'] = count($this->images)+1;
            $this->images[$id]=$info;
        }
        else
            $info=$this->images[$id];

        if($w==0 and $h==0)
        {
            $w=$info['w']/$this->k;
            $h=$info['h']/$this->k;
        }
        if($w==0)
            $w=$h*$info['w']/$info['h'];
        if($h==0)
            $h=$w*$info['h']/$info['w'];
        $this->_out(sprintf('q %.2f 0 0 %.2f %.2f %.2f cm /I%d Do Q',$w*$this->k,$h*$this->k,$x*$this->k,($this->h-($y+$h))*$this->k,$info['i']));
        if($link)
            $this->Link($x,$y,$w,$h,$link);
    }

    protected function _escapetext($s)
    {
        return strtr($s, array(')' => '\\)', '(' => '\\(', '\\' => '\\\\'));
    }

    protected function _textstring($s)
    {
        return '('. $this->_escapetext($s).')';
    }

    protected function _putstream($s)
    {
        $this->_out('stream');
        $this->_out($s);
        $this->_out('endstream');
    }

    protected function _out($s)
    {
        if($this->state==2)
            $this->pages[$this->page] .= $s."\n";
        else
            $this->buffer .= $s."\n";
    }

    protected function _getStringIW($s)
    {
        $s = (string)$s;
        $cw = &$this->CurrentFont['cw'];
        $w = 0;
        $l = strlen($s);
        for($i=0; $i<$l; $i++)
            if (isset($cw[$s{$i}]))
                $w += $cw[$s{$i}];
            else if (isset($cw[ord($s{$i})]))
                $w += $cw[ord($s{$i})];
        return ($w * $this->FontSize / 1000);
    }

    protected function _getStringIH()
    {
        return $this->FontSize;
    }

    #public
    public function __construct($orientation='P', $unit='mm', $format='A4')
    {
        $this->_dochecks();
        $this->extgstates=array();
        $this->page=0;
        $this->n=2;
        $this->buffer='';
        $this->gradients=array();
        $this->pages=array();
        $this->OrientationChanges=array();
        $this->state=0;
        $this->fonts=array();
        $this->FontFiles=array();
        $this->diffs=array();
        $this->images=array();
        $this->links=array();
        $this->InFooter=false;
        $this->lasth=0;
        $this->FontFamily='';
        $this->FontAngle=0;
        $this->FontStyle='';
        $this->FontSizePt=12;
        $this->underline=false;
        $this->DrawColor='0 G';
        $this->FillColor='0 g';
        $this->TextColor='0 g';
        $this->ColorFlag=false;
        $this->fillcomplex=false;
        $this->ws=0;
        $this->Dashes=0;
        $this->Alpha=0;
        $this->CoreFonts=array(
        'courier'=>'Courier',
        'courierb'=>'Courier-Bold',
        'courieri'=>'Courier-Oblique',
        'courierbi'=>'Courier-BoldOblique',
        'helvetica'=>'Helvetica',
        'helveticab'=>'Helvetica-Bold',
        'helveticai'=>'Helvetica-Oblique',
        'helveticabi'=>'Helvetica-BoldOblique',
        'times'=>'Times-Roman',
        'timesb'=>'Times-Bold',
        'timesi'=>'Times-Italic',
        'timesbi'=>'Times-BoldItalic',
        'symbol'=>'Symbol',
        'zapfdingbats'=>'ZapfDingbats'
        );

        switch (strtolower($unit))
        {
            case 'pt': {$this->k=1; break;}
            case 'mm': {$this->k=72/25.4; break;}
            case 'cm': {$this->k=72/2.54;; break;}
            case 'in': {$this->k=72;; break;}
            default : {$this->Error('Incorrect unit: '.$unit); break;}
        }

        if(is_string($format))
        {
            switch (strtoupper($format))
            {
                case '4A0': $format = array(4767.87,6740.79); break;
                case '2A0': $format = array(3370.39,4767.87); break;
                case 'A0': $format = array(2383.94,3370.39); break;
                case 'A1': $format = array(1683.78,2383.94); break;
                case 'A2': $format = array(1190.55,1683.78); break;
                case 'A3': $format = array(841.89,1190.55); break;
                case 'A4': default: $format = array(595.28,841.89); break;
                case 'A5': $format = array(419.53,595.28); break;
                case 'A6': $format = array(297.64,419.53); break;
                case 'A7': $format = array(209.76,297.64); break;
                case 'A8': $format = array(147.40,209.76); break;
                case 'A9': $format = array(104.88,147.40); break;
                case 'A10': $format = array(73.70,104.88); break;
                case 'B0': $format = array(2834.65,4008.19); break;
                case 'B1': $format = array(2004.09,2834.65); break;
                case 'B2': $format = array(1417.32,2004.09); break;
                case 'B3': $format = array(1000.63,1417.32); break;
                case 'B4': $format = array(708.66,1000.63); break;
                case 'B5': $format = array(498.90,708.66); break;
                case 'B6': $format = array(354.33,498.90); break;
                case 'B7': $format = array(249.45,354.33); break;
                case 'B8': $format = array(175.75,249.45); break;
                case 'B9': $format = array(124.72,175.75); break;
                case 'B10': $format = array(87.87,124.72); break;
                case 'C0': $format = array(2599.37,3676.54); break;
                case 'C1': $format = array(1836.85,2599.37); break;
                case 'C2': $format = array(1298.27,1836.85); break;
                case 'C3': $format = array(918.43,1298.27); break;
                case 'C4': $format = array(649.13,918.43); break;
                case 'C5': $format = array(459.21,649.13); break;
                case 'C6': $format = array(323.15,459.21); break;
                case 'C7': $format = array(229.61,323.15); break;
                case 'C8': $format = array(161.57,229.61); break;
                case 'C9': $format = array(113.39,161.57); break;
                case 'C10': $format = array(79.37,113.39); break;
                case 'RA0': $format = array(2437.80,3458.27); break;
                case 'RA1': $format = array(1729.13,2437.80); break;
                case 'RA2': $format = array(1218.90,1729.13); break;
                case 'RA3': $format = array(864.57,1218.90); break;
                case 'RA4': $format = array(609.45,864.57); break;
                case 'SRA0': $format = array(2551.18,3628.35); break;
                case 'SRA1': $format = array(1814.17,2551.18); break;
                case 'SRA2': $format = array(1275.59,1814.17); break;
                case 'SRA3': $format = array(907.09,1275.59); break;
                case 'SRA4': $format = array(637.80,907.09); break;
                case 'LETTER': $format = array(612.00,792.00); break;
                case 'LEGAL': $format = array(612.00,1008.00); break;
                case 'EXECUTIVE': $format = array(521.86,756.00); break;
                case 'FOLIO': $format = array(612.00,936.00); break;
            }
            $this->fwPt=$format[0];
            $this->fhPt=$format[1];
        }
        else
        {
            $this->fwPt=$format[0]*$this->k;
            $this->fhPt=$format[1]*$this->k;
        }

        $this->fw=$this->fwPt/$this->k;
        $this->fh=$this->fhPt/$this->k;

        $orientation=strtolower($orientation);
        if($orientation=='p' or $orientation=='portrait')
        {
            $this->DefOrientation='P';
            $this->wPt=$this->fwPt;
            $this->hPt=$this->fhPt;
        }
        elseif($orientation=='l' or $orientation=='landscape')
        {
            $this->DefOrientation='L';
            $this->wPt=$this->fhPt;
            $this->hPt=$this->fwPt;
        }
        else
            $this->Error('Incorrect orientation: '.$orientation);

        $this->CurOrientation=$this->DefOrientation;
        $this->w=$this->wPt/$this->k;
        $this->h=$this->hPt/$this->k;
        $margin=28.35/$this->k;
        $this->SetMargins($margin,$margin);
        $this->cMargin=$margin/10;
        $this->LineWidth=.567/$this->k;
        $this->SetAutoPageBreak(true,2*$margin);
        $this->SetDisplayMode('fullwidth');
        $this->SetCompression(true);
        $this->PDFVersion = "1.4";
    }

    public function setImageScale($scale)
    {
        $this->imgscale=$scale;
    }

    public function getImageScale()
    {
        return $this->imgscale;
    }

    public function getPageWidth()
    {
        return $this->w;
    }

    public function getPageHeight()
    {
        return $this->h;
    }

    public function getBreakMargin()
    {
        return $this->bMargin;
    }

    public function getScaleFactor()
    {
        return $this->k;
    }

    public function SetMargins($left, $top, $right=-1)
    {
        $this->lMargin=$left;
        $this->tMargin=$top;
        if($right==-1)
            $right=$left;
        $this->rMargin=$right;
    }

    public function GetLeftMargin()
    {
        return $this->lMargin;
    }

    public function GetTopMargin()
    {
        return $this->tMargin;
    }

    public function GetRightMargin()
    {
        return $this->rMargin;
    }

    public function SetLeftMargin($margin)
    {
        $this->lMargin=$margin;
        if(($this->page>0) and ($this->x<$margin))
            $this->x=$margin;
    }

    public function SetTopMargin($margin)
    {
        $this->tMargin=$margin;
    }

    public function SetRightMargin($margin)
    {
        $this->rMargin=$margin;
    }

    public function SetAutoPageBreak($auto, $margin=0)
    {
        $this->AutoPageBreak=$auto;
        $this->bMargin=$margin;
        $this->PageBreakTrigger=$this->h-$margin;
    }

    public function SetDisplayMode($zoom, $layout='continuous')
    {
        if($zoom=='fullpage' or $zoom=='fullwidth' or $zoom=='real' or $zoom=='default' or !is_string($zoom))
            $this->ZoomMode=$zoom;
        else
            $this->Error('Incorrect zoom display mode: '.$zoom);
        if($layout=='single' or $layout=='continuous' or $layout=='two' or $layout=='default')
            $this->LayoutMode=$layout;
        else
            $this->Error('Incorrect layout display mode: '.$layout);
    }

    public function SetCompression($compress)
    {
        if(function_exists('gzcompress'))
            $this->compress=$compress;
        else
            $this->compress=false;
    }

    public function SetTitle($title)

    {
        $this->title=$title;
    }

    public function SetSubject($subject)
    {
        $this->subject=$subject;
    }

    public function SetAuthor($author)
    {
        $this->author=$author;
    }

    public function SetKeywords($keywords)
    {
        $this->keywords=$keywords;
    }

    public function SetCreator($creator)
    {
        $this->creator=$creator;
    }

    public function AliasNbPages($alias='{nb}')
    {
        $this->AliasNbPages = $this->_escapetext($alias);
    }

    public function Error($msg)
    {
        die('<strong>cPdfWriter error: </strong>'.$msg);
    }

    public function Open()
    {
        $this->state=1;
    }

    public function Close()
    {
        if($this->state==3)
            return;
        if($this->page==0)
            $this->AddPage();
        $this->InFooter=true;
        $this->Footer();
        $this->InFooter=false;
        $this->_endpage();
        $this->_enddoc();
    }

    public function AddPage($orientation='')
    {
        if($this->state==0)
            $this->Open();
        $family=$this->FontFamily;
        $style=$this->FontStyle.($this->underline ? 'U' : '');
        $size=$this->FontSizePt;
        $lw=$this->LineWidth;
        $dc=$this->DrawColor;
        $fc=$this->FillColor;
        $tc=$this->TextColor;
        $cf=$this->ColorFlag;
        if($this->page>0) {
            $this->InFooter=true;
            $this->Footer();
            $this->InFooter=false;
            $this->_endpage();
        }
        $this->_beginpage($orientation);
        $this->_out('2 J');
        $this->LineWidth=$lw;
        $this->_out(sprintf('%.2f w',$lw*$this->k));
        if($family)
            $this->SetFont($family,$style,$size);
        $this->DrawColor=$dc;
        if($dc!='0 G')
            $this->_out($dc);
        $this->FillColor=$fc;
        if($fc!='0 g')
            $this->_out($fc);
        $this->TextColor=$tc;
        $this->ColorFlag=$cf;
        $this->Header();
        if($this->LineWidth!=$lw) {
            $this->LineWidth=$lw;
            $this->_out(sprintf('%.2f w',$lw*$this->k));
        }
        if($family)
            $this->SetFont($family,$style,$size);
        if($this->DrawColor!=$dc) {
            $this->DrawColor=$dc;
            $this->_out($dc);
        }
        if($this->FillColor!=$fc) {
            $this->FillColor=$fc;
            $this->_out($fc);
        }
        $this->TextColor=$tc;
        $this->ColorFlag=$cf;
    }

    public function Header()
    {
        //To be implemented in your own inherited class
    }

    public function Footer()
    {
        //To be implemented in your own inherited class
    }

    public function PageNo()
    {
        return $this->page;
    }

    public function SetAlpha($prAlpha)
    {
        if($prAlpha==$this->Alpha)
            return;
        $this->Alpha=$prAlpha;
        if($prAlpha===NULL)
            $prAlpha=255;
        $a=$prAlpha*0.0039216;
        if($a>1)$a=1;
        $n=count($this->extgstates)+1;
        $this->extgstates[$n]['parms'] = array('ca'=>$a, 'CA'=>$a, 'BM'=>'/Normal');
        $this->_out(sprintf('/GS%d gs', $n));
    }

    public function SetDrawColor($r, $g=-1, $b=-1, $prDash=0)
    {
        if(($r==0 and $g==0 and $b==0) or $g==-1)
            $this->DrawColor=sprintf('%.3f G',$r/255);
        else
            $this->DrawColor=sprintf('%.3f %.3f %.3f RG',$r/255,$g/255,$b/255);
        if($this->page>0)
            $this->_out($this->DrawColor);
        if($prDash!=$this->Dashes)
        {
            $this->Dashes=$prDash;
            $dash_string = '';
            if($prDash!==0 && is_array($prDash))
                foreach ($prDash as $i => $v)
                {
                    if ($i > 0)
                        $dash_string .= ' ';
                    $dash_string .= sprintf('%.2f', $v);
                }
            $this->_out(sprintf('[%s] 0.00 d', $dash_string));
        }
    }

    public function SetFillColor($r, $g=-1, $b=-1)
    {
        $this->fillcomplex=false;
        if(($r==0 and $g==0 and $b==0) or $g==-1)
            $this->FillColor=sprintf('%.3f g',$r/255);
        else
            $this->FillColor=sprintf('%.3f %.3f %.3f rg',$r/255,$g/255,$b/255);
        $this->ColorFlag=($this->FillColor!=$this->TextColor);
        if($this->page>0)
            $this->_out($this->FillColor);
    }

    public function SetFillGradient($r0, $g0, $b0, $r1, $g1, $b1, $type='horizontal')
    {
        $this->fillcomplex=array($r0, $g0, $b0, $r1, $g1, $b1, $type);
        $this->ColorFlag=true;
    }

    public function SetFillImage($image)
    {
        $this->fillcomplex=$image;
        $this->ColorFlag=true;
        if(!file_exists($image))
            $this->SetFillColor(255,255,255);
    }

    public function SetTextColor($r, $g=-1, $b=-1)
    {
        if(($r==0 and $g==0 and $b==0) or $g==-1)
            $this->TextColor=sprintf('%.3f g',$r/255);
        else
            $this->TextColor=sprintf('%.3f %.3f %.3f rg',$r/255,$g/255,$b/255);
        $this->ColorFlag=($this->FillColor!=$this->TextColor);
    }

    public function GetStringWidth($s)
    {
        switch(abs($this->FontAngle))
        {
        case 90:
        case 270:
            return $this->_getStringIH();
        default:
            return $this->_getStringIW($s);
        }
    }

    public function GetStringHeight($s)
    {
        switch(abs($this->FontAngle))
        {
        case 90:
        case 270:
            return $this->_getStringIW($s);
        default:
            return $this->_getStringIH();
        }
    }

    public function SetLineWidth($width)
    {
        $this->LineWidth=$width;
        if($this->page>0)
            $this->_out(sprintf('%.2f w',$width*$this->k));
    }

    public function Line($x1, $y1, $x2, $y2)
    {
        $this->_out(sprintf('%.2f %.2f m %.2f %.2f l S', $x1*$this->k, ($this->h-$y1)*$this->k, $x2*$this->k, ($this->h-$y2)*$this->k));
    }

    public function Rect($x, $y, $w, $h, $style='')
    {
        $fc=false;
        if($style=='F')
        {
            if($this->fillcomplex)
            {
                $fc=true;
                $this->_out('q');
                $op='W n';
            }
            else
                $op='f';
        }
        elseif($style=='FD' or $style=='DF')
        {
            if($this->fillcomplex)
            {
                $fc=true;
                $this->_out('q');
                $op='W s';
            }
            else
                $op='B';
        }
        else
            $op='S';

        $this->_out(sprintf('%.2f %.2f %.2f %.2f re %s',$x*$this->k,($this->h-$y)*$this->k,$w/$this->k,-$h*$this->k,$op));

        if($fc)
        {
            if(is_array($this->fillcomplex))
            {
                $this->_out(sprintf(' %.3f 0 0 %.3f %.3f %.3f cm', $w*$this->k, $h*$this->k, $x*$this->k, ($this->h-($y+$h))*$this->k));
                $this->_fillGradient($this->fillcomplex);
            }
            else
                $this->_fillImage($this->fillcomplex,$x*$this->k,$y*$this->k,$w*$this->k,$h*$this->k);
            $this->_out('Q');
        }
    }

    function Polygon($p, $connectEnds=true, $style='')
    {
        $fc=false;
        $np = count($p);
        switch ($style)
        {
            case 'F':
                if(!$connectEnds)
                    $op = 'S';
                else
                    if($this->fillcomplex)
                    {
                        $fc=true;
                        $this->_out('q');
                        $op='W n';
                    }
                    else
                        $op = 'f';
                break;
            case 'D':
                if(!$connectEnds)
                    $op = 'S';
                else
                    $op = 's';
                break;
            default:
                if(!$connectEnds)
                    $op = 'S';
                else
                    if($this->fillcomplex)
                    {
                        $fc=true;
                        $this->_out('q');
                        $op='W s';
                    }
                    else
                        $op = 'B';
                break;
        }
        $this->_out(sprintf('%.2f %.2f m', $p[0]['X'] * $this->k, ($this->h - $p[0]['Y']) * $this->k));
        $w=$x=$p[0]['X'];
        $h=$y=$p[0]['Y'];
        for ($i = 1; $i < $np; $i++)
        {
            if($x>$p[$i]['X'])
                $x=$p[$i]['X'];
            if($y>$p[$i]['Y'])
                $y=$p[$i]['Y'];
            if($w<$p[$i]['X'])
                $w=$p[$i]['X'];
            if($h<$p[$i]['Y'])
                $h=$p[$i]['Y'];
            $this->_out(sprintf('%.2f %.2f l', $p[$i]['X'] * $this->k, ($this->h - $p[$i]['Y']) * $this->k));
        }
        if($connectEnds)
            $this->_out(sprintf('%.2f %.2f l', $p[0]['X'] * $this->k, ($this->h - $p[0]['Y']) * $this->k));
        $this->_out($op);
        $w-=$x;
        $h-=$y;
        if($fc)
        {
            if(is_array($this->fillcomplex))
            {
                $this->_out(sprintf(' %.3f 0 0 %.3f %.3f %.3f cm', $w*$this->k, $h*$this->k, $x*$this->k, ($this->h-($y+$h))*$this->k));
                $this->_fillGradient($this->fillcomplex);
            }
            else
                $this->_fillImage($this->fillcomplex,$x*$this->k,$y*$this->k,$w*$this->k,$h*$this->k);
            $this->_out('Q');
        }
    }

    function Ellipse($prX0,$prY0,$prRx,$prRy,$prSrx,$prSry,$style='',$prAngle=0,$prAstart=0,$prAfinish=360,$prNseg=8)
    {
        $fc=false;
        $x=$prX0-$prRx;
        $y=$prY0-$prRy;
        $w=$prRx*2;
        $h=$prRy*2;

        $e=abs($prAfinish-$prAstart)<360?true:false;
        switch ($style)
        {
            case 'F':
                if($this->fillcomplex)
                {
                    $fc=true;
                    $this->_out('q');
                    $op='W n';
                }
                else
                    $op = 'f';
                $line_style = null;
                break;
            case 'FD': case 'DF':
                if($this->fillcomplex)
                {
                    $fc=true;
                    $this->_out('q');
                    $op='W s';
                }
                else
                    $op = 'B';
                break;
            default:
                $op = 'S';
                break;
        }

        $xx=$prX0;
        $yy=$prY0;

        if (!$prRy)
            $prRy = $prRx;
        $prRx *= $this->k;
        $prRy *= $this->k;
        if ($prNseg < 2)
            $prNseg = 2;

        $prAstart = deg2rad((float) $prAstart);
        $prAfinish = deg2rad((float) $prAfinish);
        $totalAngle = $prAfinish - $prAstart;
        $dt = $totalAngle/$prNseg;
        $dtm = $dt/3;
        $xx *= $this->k;
        $yy = ($this->h - $yy) * $this->k;
        if ($prAngle != 0)
        {
            $a = -deg2rad((float) $prAngle);
            $this->_out(sprintf('q %.2f %.2f %.2f %.2f %.2f %.2f cm', cos($a), -1 * sin($a), sin($a), cos($a), $xx, $yy));
            $xx = 0;
            $yy = 0;
        }
        $t1 = $prAstart;
        $aa0=$a0 = $xx + ($prRx * cos($t1));
        $bb0=$b0 = $yy + ($prRy * sin($t1));
        $c0 = -$prRx * sin($t1);
        $d0 = $prRy * cos($t1);
        $this->_out(sprintf('%.2f %.2f m', ($a0 / $this->k) * $this->k, ($this->h - ($this->h - ($b0 / $this->k))) * $this->k));
        for ($i = 1; $i <= $prNseg; $i++)
        {
            $t1 = ($i * $dt) + $prAstart;
            $a1 = $xx + ($prRx * cos($t1));
            $b1 = $yy + ($prRy * sin($t1));
            $c1 = -$prRx * sin($t1);
            $d1 = $prRy * cos($t1);
            $this->_out(sprintf('%.2f %.2f %.2f %.2f %.2f %.2f c',
                        (($a0 + ($c0 * $dtm)) / $this->k) * $this->k,
                        ($this->h - ($this->h - (($b0 + ($d0 * $dtm)) / $this->k))) * $this->k,
                        (($a1 - ($c1 * $dtm)) / $this->k) * $this->k,
                        ($this->h - ($this->h - (($b1 - ($d1 * $dtm)) / $this->k))) * $this->k,
                        ($a1 / $this->k) * $this->k,
                        ($this->h - ($this->h - ($b1 / $this->k))) * $this->k));

            $a0 = $a1;
            $b0 = $b1;
            $c0 = $c1;
            $d0 = $d1;
        }
        if($prSrx || $e)
        {
            if (!$prSry)
                $prSry = $prSrx;
            $t1 = $prAfinish;
            $prSrx *= $this->k;
            $prSry *= $this->k;
            $a0 = $xx + ($prSrx * cos($t1));
            $b0 = $yy + ($prSry * sin($t1));
            $c0 = -$prSrx * sin($t1);
            $d0 = $prSry * cos($t1);
            if($e)
                $this->_out(sprintf('%.2f %.2f l', ($a0 / $this->k) * $this->k, ($this->h - ($this->h - ($b0 / $this->k))) * $this->k));
            else
                $this->_out(sprintf('%.2f %.2f m', ($a0 / $this->k) * $this->k, ($this->h - ($this->h - ($b0 / $this->k))) * $this->k));
            for ($i = 1; $i <= $prNseg; $i++) {
                $t1 = $prAfinish-($i*$dt);
                $a1 = $xx + ($prSrx * cos($t1));
                $b1 = $yy + ($prSry * sin($t1));
                $c1 = -$prSrx * sin($t1);
                $d1 = $prSry * cos($t1);
                $this->_out(sprintf('%.2f %.2f %.2f %.2f %.2f %.2f c',
                            (($a0 - ($c0 * $dtm)) / $this->k) * $this->k,
                            ($this->h - ($this->h - (($b0 - ($d0 * $dtm)) / $this->k))) * $this->k,
                            (($a1 + ($c1 * $dtm)) / $this->k) * $this->k,
                            ($this->h - ($this->h - (($b1 + ($d1 * $dtm)) / $this->k))) * $this->k,
                            ($a1 / $this->k) * $this->k,
                            ($this->h - ($this->h - ($b1 / $this->k))) * $this->k));

                $a0 = $a1;
                $b0 = $b1;
                $c0 = $c1;
                $d0 = $d1;
            }
        }
        if($e)
            $this->_out(sprintf('%.2f %.2f l', ($aa0 / $this->k) * $this->k, ($this->h - ($this->h - ($bb0 / $this->k))) * $this->k));
        $this->_out($op);
        if($fc)
        {
            if(is_array($this->fillcomplex))
            {
                $this->_out(sprintf(' %.3f 0 0 %.3f %.3f %.3f cm', $w*$this->k, $h*$this->k, $x*$this->k, ($this->h-($y+$h))*$this->k));
                $this->_fillGradient($this->fillcomplex);
            }
            else
                $this->_fillImage($this->fillcomplex);
        }
        if ($fc || $prAngle !=0)
            $this->_out('Q');
    }

    public function AddFont($family, $style='', $file='')
    {
        if(empty($family))
            return;

        $family = strtolower($family);
        if($family == 'arial')
            $family = 'helvetica';

        $style=strtoupper($style);
        $style=str_replace('U','',$style);
        if($style == 'IB')
            $style = 'BI';

        $fontkey = $family.$style;
        if(isset($this->fonts[$fontkey]))
            return;

        if($file=='')
            $file = str_replace(' ', '', $family).strtolower($style).'.php';
        if(!file_exists($this->_getfontpath().$file))
            $file = str_replace(' ', '', $family).'.php';

        include($this->_getfontpath().$file);
        if(!isset($name) AND !isset($fpdf_charwidths))
            $this->Error('Could not include font definition file');

        $i = count($this->fonts)+1;

        $this->fonts[$fontkey]=array('i'=>$i, 'type'=>'core', 'name'=>$this->CoreFonts[$fontkey], 'up'=>-100, 'ut'=>50, 'cw'=>$fpdf_charwidths[$fontkey]);

        if(isset($diff) AND (!empty($diff)))
        {
            $d=0;
            $nb=count($this->diffs);
            for($i=1;$i<=$nb;$i++)
                if($this->diffs[$i]==$diff)
                {
                    $d=$i;
                    break;
                }
            if($d==0)
            {
                $d=$nb+1;
                $this->diffs[$d]=$diff;
            }
            $this->fonts[$fontkey]['diff']=$d;
        }
        if(!empty($file))
        {
            if((strcasecmp($type,"TrueType") == 0) OR (strcasecmp($type,"TrueTypeUnicode") == 0))
                $this->FontFiles[$file]=array('length1'=>$originalsize);
            else
                $this->FontFiles[$file]=array('length1'=>$size1,'length2'=>$size2);
        }
    }

    public function SetFont($family, $style='', $size=0)
    {
        global $fpdf_charwidths;
        $family=strtolower($family);
        if($family=='') {
            $family=$this->FontFamily;
        }
        if($family == 'arial')
            $family = 'helvetica';
        elseif(($family=="symbol") OR ($family=="zapfdingbats"))
            $style='';
        $style=strtolower($style);

        if(strpos($style,'u')===false)
            $this->underline=false;
        else
        {
            $this->underline=true;
            $style=str_replace('u','',$style);
        }
        if($style=='ib')
            $style='bi';
        if($size==0)
            $size=$this->FontSizePt;

        $family=strtolower($family);
        $style=strtolower($style);
        $fontkey = $family.$style;
        if(!isset($this->CoreFonts[$fontkey]))
        {
            $family='helvetica';
            $style='';
            $fontkey='helvetica';
        }

        if(($this->FontFamily == $family) AND ($this->FontStyle == $style) AND ($this->FontSizePt == $size))
            return;

        if(!isset($this->fonts[$fontkey]))
        {
            if(!isset($fpdf_charwidths[$fontkey]))
            {
                $file = $family;
                if(($family!='symbol') AND ($family!='zapfdingbats'))
                    $file .= strtolower($style);
                if(!file_exists($this->_getfontpath().$file.'.php'))
                {
                    $file = $family;
                    $fontkey = $family;
                }
                include($this->_getfontpath().$file.'.php');
                if (!isset($fpdf_charwidths[$fontkey]))
                    $this->Error("Could not include font metric file [".$fontkey."]: ".$this->_getfontpath().$file.".php");
            }
            $i = count($this->fonts) + 1;
            $this->fonts[$fontkey]=array('i'=>$i, 'type'=>'core', 'name'=>$this->CoreFonts[$fontkey], 'up'=>-100, 'ut'=>50, 'cw'=>$fpdf_charwidths[$fontkey]);
        }
        $this->FontFamily = $family;
        $this->FontStyle = $style;
        $this->FontSizePt = $size;
        $this->FontSize = $size / $this->k;
        $this->CurrentFont = &$this->fonts[$fontkey];
        if($this->page>0)
            $this->_out(sprintf('BT /F%d %.2f Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
    }

    public function SetFontSize($size)
    {
        if($this->FontSizePt==$size)
            return;
        $this->FontSizePt = $size;
        $this->FontSize = $size / $this->k;
        if($this->page > 0)
            $this->_out(sprintf('BT /F%d %.2f Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
    }

    public function AddLink()
    {
        $n=count($this->links)+1;
        $this->links[$n]=array(0,0);
        return $n;
    }

    public function SetLink($link, $y=0, $page=-1)
    {
        if($y==-1)
            $y=$this->y;
        if($page==-1)
            $page=$this->page;
        $this->links[$link]=array($page,$y);
    }

    public function Link($x, $y, $w, $h, $link)
    {
        $this->PageLinks[$this->page][] = array($x * $this->k, $this->hPt - $y * $this->k, $w * $this->k, $h*$this->k, $link);
    }

    public function AcceptPageBreak()
    {
        return $this->AutoPageBreak;
    }

    public function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=0, $link='')
    {
        $k=$this->k;
        if(($this->y + $h) > $this->PageBreakTrigger AND empty($this->InFooter) AND $this->AcceptPageBreak())
        {
            $x = $this->x;
            $ws = $this->ws;
            if($ws > 0)
            {
                $this->ws = 0;
                $this->_out('0 Tw');
            }
            $this->AddPage($this->CurOrientation);
            $this->x = $x;
            if($ws > 0)
            {
                $this->ws = $ws;
                $this->_out(sprintf('%.3f Tw',$ws * $k));
            }
        }
        if($w == 0)
            $w = $this->w - $this->rMargin - $this->x;
        $s = '';
        if(($fill == 1) OR ($border == 1))
        {
            if($fill == 1)
                $op = ($border == 1) ? 'B' : 'f';
            else
                $op = 'S';
            $s = sprintf('%.2f %.2f %.2f %.2f re %s ', $this->x * $k, ($this->h - $this->y) * $k, $w * $k, -$h * $k, $op);
        }
        if(is_string($border))
        {
            $x=$this->x;
            $y=$this->y;
            if(strpos($border,'L')!==false)
                $s.=sprintf('%.2f %.2f m %.2f %.2f l S ',$x*$k,($this->h-$y)*$k,$x*$k,($this->h-($y+$h))*$k);
            if(strpos($border,'T')!==false)
                $s.=sprintf('%.2f %.2f m %.2f %.2f l S ',$x*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-$y)*$k);
            if(strpos($border,'R')!==false)
                $s.=sprintf('%.2f %.2f m %.2f %.2f l S ',($x+$w)*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
            if(strpos($border,'B')!==false)
                $s.=sprintf('%.2f %.2f m %.2f %.2f l S ',$x*$k,($this->h-($y+$h))*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
        }
        if($txt != '')
        {
            $width = $this->_getStringIW($txt);
            if($align == 'R')
                $dx = $w - $this->cMargin - $width;
            elseif($align=='C')
                $dx = ($w - $width)/2;
            else
                $dx = $this->cMargin;
            if($this->ColorFlag)
                $s .= 'q '.$this->TextColor.' ';
            $txt2 = $this->_escapetext($txt);
            $s.=sprintf('BT %.2f %.2f Td (%s) Tj ET', ($this->x + $dx) * $k, ($this->h - ($this->y + 0.5 * $h + 0.3 * $this->FontSize)) * $k, $txt2);
            if($this->underline)
                $s.=' '.$this->_dounderline($this->x + $dx, $this->y + 0.5 * $h + 0.3 * $this->FontSize, $txt);
            if($this->ColorFlag)
                $s.=' Q';
            if($link)
                $this->Link($this->x + $dx, $this->y + 0.5 * $h - 0.5 * $this->FontSize, $width, $this->FontSize, $link);
        }
        if($s)
            $this->_out($s);
        $this->lasth = $h;
        if($ln>0)
        {
            $this->y += $h;
            if($ln == 1)
                $this->x = $this->lMargin;
        }
        else
            $this->x += $w;
    }

    public function MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0)
    {
        $cw = &$this->CurrentFont['cw'];

        if($w == 0)
            $w = $this->w - $this->rMargin - $this->x;

        $wmax = ($w - 2 * $this->cMargin);
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);

        $b=0;
        if($border)
        {
            if($border==1)
            {
                $border='LTRB';
                $b='LRT';
                $b2='LR';
            }
            else
            {
                $b2='';
                if(strpos($border,'L')!==false)
                    $b2.='L';
                if(strpos($border,'R')!==false)
                    $b2.='R';
                $b=(strpos($border,'T')!==false) ? $b2.'T' : $b2;
            }
        }
        $sep=-1;
        $i=0;
        $j=0;
        $l=0;
        $ns=0;
        $nl=1;
        while($i<$nb)
        {
            $c = $s{$i};
            if(preg_match("/[\n]/u", $c))
            {
                if($this->ws > 0)
                {
                    $this->ws = 0;
                    $this->_out('0 Tw');
                }
                $this->Cell($w, $h, substr($s, $j, $i-$j), $b, 2, $align, $fill);
                $i++;
                $sep=-1;
                $j=$i;
                $l=0;
                $ns=0;
                $nl++;
                if($border and $nl==2)
                    $b = $b2;
                continue;
            }
            if(preg_match("/[ ]/u", $c))
            {
                $sep = $i;
                $ls = $l;
                $ns++;
            }

            $l = $this->_getStringIW(substr($s, $j, $i-$j));

            if($l > $wmax)
            {
                if($sep == -1)
                {
                    if($i == $j)
                        $i++;
                    if($this->ws > 0)
                    {
                        $this->ws = 0;
                        $this->_out('0 Tw');
                    }
                    $this->Cell($w, $h, substr($s, $j, $i-$j), $b, 2, $align, $fill);
                }
                else
                {
                    if($align=='J')
                    {
                        $this->ws = ($ns>1) ? ($wmax-$ls)/($ns-1) : 0;
                        $this->_out(sprintf('%.3f Tw', $this->ws * $this->k));
                    }
                    $this->Cell($w, $h, substr($s, $j, $sep-$j), $b, 2, $align, $fill);
                    $i = $sep + 1;
                }
                $sep=-1;
                $j=$i;
                $l=0;
                $ns=0;
                $nl++;
                if($border AND ($nl==2))
                    $b=$b2;
            }
            else
                $i++;
        }
        if($this->ws>0)
        {
            $this->ws=0;
            $this->_out('0 Tw');
        }
        if($border and is_int(strpos($border,'B')))
            $b.='B';
        $this->Cell($w, $h, substr($s, $j, $i-$j), $b, 2, $align, $fill);
        $this->x=$this->lMargin;
    }

    public function Write($txt, $h=0, $link='')
    {
        if(!$h)
            $h=$this->_getStringIH($txt);
        $cw = &$this->CurrentFont['cw'];
        $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin);
        $s = str_replace("\r", '', $txt);
        $sep=-1;
        $i=0;
        $j=0;
        $l=0;
        $nl=1;
        while($i<strlen($s))
        {
            $c=$s{$i};
            if($c=="\n")
            {
                $this->Cell($w, $h, substr($s, $j, $i-$j), 0, 2, '', 0, $link);
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                if($nl == 1)
                {
                    $this->x = $this->lMargin;
                    $w = $this->w - $this->rMargin - $this->x;
                    $wmax = ($w - 2 * $this->cMargin);
                }
                $nl++;
                continue;
            }
            if($c==' ')
                $sep= $i;

            $l = $this->_getStringIW(substr($s, $j, $i-$j));

            if($l > $wmax)
            {
                if($sep == -1)
                {
                    if($this->x > $this->lMargin)
                    {
                        $this->x = $this->lMargin;
                        $this->y += $h;
                        $w=$this->w - $this->rMargin - $this->x;
                        $wmax=($w - 2 * $this->cMargin);
                        $i++;
                        $nl++;
                        continue;
                    }
                    if($i==$j)
                        $i++;
                    $this->Cell($w, $h, substr($s, $j, $i-$j), 0, 2, '', 0, $link);
                }
                else
                {
                    $this->Cell($w, $h, substr($s, $j, $sep-$j), 0, 2, '', 0, $link);
                    $i=$sep+1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                if($nl==1)
                {
                    $this->x = $this->lMargin;
                    $w = $this->w - $this->rMargin - $this->x;
                    $wmax = ($w - 2 * $this->cMargin);
                }
                $nl++;
            }
            else
                $i++;
        }
        if($i!=$j)
            $this->Cell($l / 1000 * $this->FontSize, $h, substr($s, $j), 0, 0, '', 0, $link);
        $this->x+=$this->_getStringIW(substr($s, $j,$i-$j));
    }

    public function TextAngle($angle=0)
    {
        $this->FontAngle=($angle-$angle%90)%360;
    }

    public function Text($x,$y,$txt,$halign='left',$valign='top')
    {
        $txt=str_replace(')','\\)',str_replace('(','\\(',str_replace('\\','\\\\',$txt)));
        $w=$this->GetStringWidth($txt);
        $h=$this->GetStringHeight($txt);
        $txt_angle=$this->FontAngle;
        $dx=$dy=0;

        switch($txt_angle)
        {
        case -270:
        case 90:
            $dy=1;
            $dx=1;
            break;
        case -180:
        case 180:
            $dy=-1;
            $dx=1;
            break;
        case -90:
        case 270:
            $dy=-1;
            $dx=-1;
            break;
        default:
            $dy=1;
            $dx=-1;
            break;
        }

        if($halign=='right')
            $x+=$dx*$w;
        if($halign=='center')
            $x+=$dx*$w/2;
        if($valign=='top')
            $y+=$dy*$h;
        if($valign=='middle')
            $y+=$dy*$h/2;

        $font_angle=90+$txt_angle;
        $txt_angle*=M_PI/180;
        $font_angle*=M_PI/180;
        $txt_dx=cos($txt_angle);
        $txt_dy=sin($txt_angle);
        $font_dx=cos($font_angle);
        $font_dy=sin($font_angle);

        $s=sprintf('BT %.2f %.2f %.2f %.2f %.2f %.2f Tm (%s) Tj ET',
                 $txt_dx,$txt_dy,$font_dx,$font_dy,
                 $x*$this->k,($this->h-$y)*$this->k,$txt);
        if ($this->ColorFlag)
            $s='q '.$this->TextColor.' '.$s.' Q';
        $this->_out($s);
    }

    public function Image($file, $x, $y, $w=0, $h=0, $type='', $link='')
    {
        if(!isset($this->images[$file]))
        {
            if($type == '')
            {
                $pos = strrpos($file,'.');
                if(empty($pos))
                    $this->Error('Image file has no extension and no type was specified: '.$file);
                $type = substr($file, $pos+1);
            }
            $type = strtolower($type);
            $mqr = get_magic_quotes_runtime();
            set_magic_quotes_runtime(0);
            if($type == 'jpg' or $type == 'jpeg')
                $info=$this->_parsejpg($file);
            elseif ($type=='png')
            {
                $im=imagecreatefrompng($file);
                $this->BufferedGDImage($im,$x,$y,$w,$h,$link);
                return;
            }
            elseif($type=='gif')
            {
                $im=imagecreatefromgif($file);
                $this->BufferedGDImage($im,$x,$y,$w,$h,$link);
                return;
            }
            else
            {
                $mtd='_parse'.$type;
                if(!method_exists($this,$mtd))
                    $this->Error('Unsupported image type: '.$type);
                $info=$this->$mtd($file);
            }
            set_magic_quotes_runtime($mqr);
            $info['i']=count($this->images)+1;
            $this->images[$file]=$info;
        }
        else
            $info=$this->images[$file];
        if(($w == 0) and ($h == 0))
        {
            $w = $info['w'] / ($this->imgscale * $this->k);
            $h = $info['h'] / ($this->imgscale * $this->k);
        }
        if($w == 0)
            $w = $h * $info['w'] / $info['h'];
        if($h == 0)
            $h = $w * $info['h'] / $info['w'];
        $this->_out(sprintf('q %.3f 0 0 %.3f %.3f %.3f cm /I%d Do Q', $w*$this->k, $h*$this->k, $x*$this->k, ($this->h-($y+$h))*$this->k, $info['i']));
        if($link)
            $this->Link($x, $y, $w, $h, $link);
        $this->img_rb_x = $x + $w;
        $this->img_rb_y = $y + $h;
    }

    public function BufferedGDImage($im, $x, $y, $w=0, $h=0, $link='')
    {
        $ww=imagesx($im);
        $hh=imagesy($im);
        $im2=imagecreatetruecolor($ww,$hh);
        imageinterlace($im2,0);
        $bgcolor=imagecolorallocate($im2,255,255,255);
        imagefilledrectangle($im2,0,0,$ww-1,$hh-1,$bgcolor);
        imagecopy($im2,$im,0,0,0,0,$ww,$hh);
        imagedestroy($im);
        ob_start();
        imagepng($im2);
        $data = ob_get_contents();
        ob_end_clean();
        $this->_pngImage($data,$x,$y,$w,$h,$link);
    }

    public function Ln($h='')
    {
        $this->x=$this->lMargin;
        if(is_string($h))
            $this->y+=$this->lasth;
        else
            $this->y+=$h;
    }

    public function GetX()
    {
        return $this->x;
    }

    public function SetX($x)
    {
        if($x>=0)
            $this->x=$x;
        else
            $this->x=$this->w+$x;
    }

    public function GetY()
    {
        return $this->y;
    }

    public function SetY($y)
    {
        $this->x=$this->lMargin;
        if($y>=0)
            $this->y=$y;
        else
            $this->y=$this->h+$y;
    }

    public function SetXY($x, $y)
    {
        $this->SetY($y);
        $this->SetX($x);
    }

    public function Output($name='',$dest='')
    {
        if($this->state < 3)
            $this->Close();
        if(is_bool($dest))
            $dest=$dest ? 'D' : 'F';
        $dest=strtoupper($dest);
        if($dest=='')
        {
            if($name=='')
            {
                $name='doc.pdf';
                $dest='I';
            }
            else
                $dest='F';
        }
        switch($dest)
        {
            case 'I':
                if(ob_get_contents())
                    $this->Error('Some data has already been output, can\'t send PDF file');
                if(php_sapi_name()!='cli')
                {
                    header('Content-Type: application/pdf');
                    if(headers_sent())
                        $this->Error('Some data has already been output to browser, can\'t send PDF file');
                    header('Content-Length: '.strlen($this->buffer));
                    header('Content-disposition: inline; filename="'.$name.'"');
                }
                echo $this->buffer;
                break;
            case 'D':
                if(ob_get_contents())
                    $this->Error('Some data has already been output, can\'t send PDF file');
                if(isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'],'MSIE'))
                    header('Content-Type: application/force-download');
                else
                    header('Content-Type: application/octet-stream');
                if(headers_sent())
                    $this->Error('Some data has already been output to browser, can\'t send PDF file');
                header('Content-Length: '.strlen($this->buffer));
                header('Content-disposition: attachment; filename="'.$name.'"');
                echo $this->buffer;
                break;
            case 'F':
                $f=fopen($name,'wb');
                if(!$f)
                    $this->Error('Unable to create output file: '.$name);
                fwrite($f,$this->buffer,strlen($this->buffer));
                fclose($f);
                break;
            case 'S':
                return $this->buffer;
            default:
                $this->Error('Incorrect output destination: '.$dest);
        }
        return '';
    }
}

?>
