.. include:: /Includes.rst.txt
.. index:: highlighting

============
Highlighting
============

Examples taken from https://highlightjs.org/static/demo/

.. contents:: This page
   :backlinks: top
   :class: compact-list
   :depth: 99
   :local:


If highlighting is missing it may be due to the fact
that the pygments lexer has detected a syntax error.
Code needs to be syntactically correct.


PHP
===

.. highlight:: php

Thi snippet is `test.php` of the pygments test suite::

   <?php

   $disapproval_ಠ_ಠ_of_php = 'unicode var';

   $test = function($a) { $lambda = 1; }

   /**
    *  Zip class file
    *
    *  @package     fnord.bb
    *  @subpackage  archive
    */

   // Unlock?
   if(!defined('UNLOCK') || !UNLOCK)
     die();

   // Load the parent archive class
   require_once(ROOT_PATH.'/classes/archive.class.php');

   class Zip\Zippಠ_ಠ_ {

   }

   /**
    *  Zip class
    *
    *  @author      Manni <manni@fnord.name>
    *  @copyright   Copyright (c) 2006, Manni
    *  @version     1.0
    *  @link        http://www.pkware.com/business_and_developers/developer/popups/appnote.txt
    *  @link        http://mannithedark.is-a-geek.net/
    *  @since       1.0
    *  @package     fnord.bb
    *  @subpackage  archive
    */
   class Zip extends Archive {
    /**
     *  Outputs the zip file
     *
     *  This function creates the zip file with the dirs and files given.
     *  If the optional parameter $file is given, the zip file is will be
     *  saved at that location. Otherwise the function returns the zip file's content.
     *
     *  @access                   public
     *
     *  @link                     http://www.pkware.com/business_and_developers/developer/popups/appnote.txt
     *  @param  string $filename  The path where the zip file will be saved
     *
     *  @return bool|string       Returns either true if the fil is sucessfully created or the content of the zip file
     */
     function out($filename = false) {
       // Empty output
       $file_data = array(); // Data of the file part
       $cd_data   = array(); // Data of the central directory

       // Sort dirs and files by path length
       uksort($this->dirs,  'sort_by_length');
       uksort($this->files, 'sort_by_length');

       // Handle dirs
       foreach($this->dirs as $dir) {
         $dir .= '/';
         // File part

         // Reset dir data
         $dir_data = '';

         // Local file header
         $dir_data .= "\x50\x4b\x03\x04";      // Local file header signature
         $dir_data .= pack("v", 10);           // Version needed to extract
         $dir_data .= pack("v", 0);            // General purpose bit flag
         $dir_data .= pack("v", 0);            // Compression method
         $dir_data .= pack("v", 0);            // Last mod file time
         $dir_data .= pack("v", 0);            // Last mod file date
         $dir_data .= pack("V", 0);            // crc-32
         $dir_data .= pack("V", 0);            // Compressed size
         $dir_data .= pack("V", 0);            // Uncompressed size
         $dir_data .= pack("v", strlen($dir)); // File name length
         $dir_data .= pack("v", 0);            // Extra field length

         $dir_data .= $dir;                    // File name
         $dir_data .= '';                      // Extra field (is empty)

         // File data
         $dir_data .= '';                      // Dirs have no file data

         // Data descriptor
         $dir_data .= pack("V", 0);            // crc-32
         $dir_data .= pack("V", 0);            // Compressed size
         $dir_data .= pack("V", 0);            // Uncompressed size

         // Save current offset
         $offset = strlen(implode('', $file_data));

         // Append dir data to the file part
         $file_data[] = $dir_data;

         // Central directory

         // Reset dir data
         $dir_data = '';

         // File header
         $dir_data .= "\x50\x4b\x01\x02";      // Local file header signature
         $dir_data .= pack("v", 0);            // Version made by
         $dir_data .= pack("v", 10);           // Version needed to extract
         $dir_data .= pack("v", 0);            // General purpose bit flag
         $dir_data .= pack("v", 0);            // Compression method
         $dir_data .= pack("v", 0);            // Last mod file time
         $dir_data .= pack("v", 0);            // Last mod file date
         $dir_data .= pack("V", 0);            // crc-32
         $dir_data .= pack("V", 0);            // Compressed size
         $dir_data .= pack("V", 0);            // Uncompressed size
         $dir_data .= pack("v", strlen($dir)); // File name length
         $dir_data .= pack("v", 0);            // Extra field length
         $dir_data .= pack("v", 0);            // File comment length
         $dir_data .= pack("v", 0);            // Disk number start
         $dir_data .= pack("v", 0);            // Internal file attributes
         $dir_data .= pack("V", 16);           // External file attributes
         $dir_data .= pack("V", $offset);      // Relative offset of local header

         $dir_data .= $dir;                    // File name
         $dir_data .= '';                      // Extra field (is empty)
         $dir_data .= '';                      // File comment (is empty)

         /*
         // Data descriptor
         $dir_data .= pack("V", 0);            // crc-32
         $dir_data .= pack("V", 0);            // Compressed size
         $dir_data .= pack("V", 0);            // Uncompressed size
         */

         // Append dir data to the central directory data
         $cd_data[] = $dir_data;
       }

       // Handle files
       foreach($this->files as $name => $file) {
         // Get values
         $content = $file[0];

         // File part

         // Reset file data
         $fd = '';

         // Detect possible compressions
         // Use deflate
         if(function_exists('gzdeflate')) {
           $method = 8;

           // Compress file content
           $compressed_data = gzdeflate($content);

         // Use bzip2
         } elseif(function_exists('bzcompress')) {
           $method = 12;

           // Compress file content
           $compressed_data = bzcompress($content);

         // No compression
         } else {
           $method = 0;

           // Do not compress the content :P
           $compressed_data = $content;
         }

         // Local file header
         $fd .= "\x50\x4b\x03\x04";                  // Local file header signature
         $fd .= pack("v", 20);                       // Version needed to extract
         $fd .= pack("v", 0);                        // General purpose bit flag
         $fd .= pack("v", $method);                  // Compression method
         $fd .= pack("v", 0);                        // Last mod file time
         $fd .= pack("v", 0);                        // Last mod file date
         $fd .= pack("V", crc32($content));          // crc-32
         $fd .= pack("V", strlen($compressed_data)); // Compressed size
         $fd .= pack("V", strlen($content));         // Uncompressed size
         $fd .= pack("v", strlen($name));            // File name length
         $fd .= pack("v", 0);                        // Extra field length

         $fd .= $name;                               // File name
         $fd .= '';                                  // Extra field (is empty)

         // File data
         $fd .= $compressed_data;

         // Data descriptor
         $fd .= pack("V", crc32($content));          // crc-32
         $fd .= pack("V", strlen($compressed_data)); // Compressed size
         $fd .= pack("V", strlen($content));         // Uncompressed size

         // Save current offset
         $offset = strlen(implode('', $file_data));

         // Append file data to the file part
         $file_data[] = $fd;

         // Central directory

         // Reset file data
         $fd = '';

         // File header
         $fd .= "\x50\x4b\x01\x02";                  // Local file header signature
         $fd .= pack("v", 0);                        // Version made by
         $fd .= pack("v", 20);                       // Version needed to extract
         $fd .= pack("v", 0);                        // General purpose bit flag
         $fd .= pack("v", $method);                  // Compression method
         $fd .= pack("v", 0);                        // Last mod file time
         $fd .= pack("v", 0);                        // Last mod file date
         $fd .= pack("V", crc32($content));          // crc-32
         $fd .= pack("V", strlen($compressed_data)); // Compressed size
         $fd .= pack("V", strlen($content));         // Uncompressed size
         $fd .= pack("v", strlen($name));            // File name length
         $fd .= pack("v", 0);                        // Extra field length
         $fd .= pack("v", 0);                        // File comment length
         $fd .= pack("v", 0);                        // Disk number start
         $fd .= pack("v", 0);                        // Internal file attributes
         $fd .= pack("V", 32);                       // External file attributes
         $fd .= pack("V", $offset);                  // Relative offset of local header

         $fd .= $name;                               // File name
         $fd .= '';                                  // Extra field (is empty)
         $fd .= '';                                  // File comment (is empty)

         /*
         // Data descriptor
         $fd .= pack("V", crc32($content));          // crc-32
         $fd .= pack("V", strlen($compressed_data)); // Compressed size
         $fd .= pack("V", strlen($content));         // Uncompressed size
         */

         // Append file data to the central directory data
         $cd_data[] = $fd;
       }

       // Digital signature
       $digital_signature = '';
       $digital_signature .= "\x50\x4b\x05\x05";  // Header signature
       $digital_signature .= pack("v", 0);        // Size of data
       $digital_signature .= '';                  // Signature data (is empty)

       $tmp_file_data = implode('', $file_data);  // File data
       $tmp_cd_data   = implode('', $cd_data).    // Central directory
                        $digital_signature;       // Digital signature

       // End of central directory
       $eof_cd = '';
       $eof_cd .= "\x50\x4b\x05\x06";                // End of central dir signature
       $eof_cd .= pack("v", 0);                      // Number of this disk
       $eof_cd .= pack("v", 0);                      // Number of the disk with the start of the central directory
       $eof_cd .= pack("v", count($cd_data));        // Total number of entries in the central directory on this disk
       $eof_cd .= pack("v", count($cd_data));        // Total number of entries in the central directory
       $eof_cd .= pack("V", strlen($tmp_cd_data));   // Size of the central directory
       $eof_cd .= pack("V", strlen($tmp_file_data)); // Offset of start of central directory with respect to the starting disk number
       $eof_cd .= pack("v", 0);                      // .ZIP file comment length
       $eof_cd .= '';                                // .ZIP file comment (is empty)

       // Content of the zip file
       $data = $tmp_file_data.
               // $extra_data_record.
               $tmp_cd_data.
               $eof_cd;

       // Return content?
       if(!$filename)
         return $data;

       // Write to file
       return file_put_contents($filename, $data);
     }

    /**
     *  Load a zip file
     *
     *  This function loads the files and dirs from a zip file from the harddrive.
     *
     *  @access                public
     *
     *  @param  string $file   The path to the zip file
     *  @param  bool   $reset  Reset the files and dirs before adding the zip file's content?
     *
     *  @return bool           Returns true if the file was loaded sucessfully
     */
     function load_file($file, $reset = true) {
       // Check whether the file exists
       if(!file_exists($file))
         return false;

       // Load the files content
       $content = @file_get_contents($file);

       // Return false if the file cannot be opened
       if(!$content)
         return false;

       // Read the zip
       return $this->load_string($content, $reset);
     }

    /**
     *  Load a zip string
     *
     *  This function loads the files and dirs from a string
     *
     *  @access                 public
     *
     *  @param  string $string  The string the zip is generated from
     *  @param  bool   $reset   Reset the files and dirs before adding the zip file's content?
     *
     *  @return bool            Returns true if the string was loaded sucessfully
     */
     function load_string($string, $reset = true) {
       // Reset the zip?
       if($reset) {
         $this->dirs  = array();
         $this->files = array();
       }

       // Get the starting position of the end of central directory record
       $start = strpos($string, "\x50\x4b\x05\x06");

       // Error
       if($start === false)
         die('Could not find the end of central directory record');

       // Get the ecdr
       $eof_cd = substr($string, $start+4, 18);

       // Unpack the ecdr infos
       $eof_cd = unpack('vdisc1/'.
                        'vdisc2/'.
                        'ventries1/'.
                        'ventries2/'.
                        'Vsize/'.
                        'Voffset/'.
                        'vcomment_lenght', $eof_cd);

       // Do not allow multi disc zips
       if($eof_cd['disc1'] != 0)
         die('multi disk stuff is not yet implemented :/');

       // Save the interesting values
       $cd_entries = $eof_cd['entries1'];
       $cd_size    = $eof_cd['size'];
       $cd_offset  = $eof_cd['offset'];

       // Get the central directory record
       $cdr = substr($string, $cd_offset, $cd_size);

       // Reset the position and the list of the entries
       $pos     = 0;
       $entries = array();

       // Handle cdr
       while($pos < strlen($cdr)) {
         // Check header signature
         // Digital signature
         if(substr($cdr, $pos, 4) == "\x50\x4b\x05\x05") {
           // Get digital signature size
           $tmp_info = unpack('vsize', substr($cdr, $pos + 4, 2));

           // Read out the digital signature
           $digital_sig = substr($header, $pos + 6, $tmp_info['size']);

           break;
         }

         // Get file header
         $header = substr($cdr, $pos, 46);

         // Unpack the header information
         $header_info = @unpack('Vheader/'.
                                'vversion_made_by/'.
                                'vversion_needed/'.
                                'vgeneral_purpose/'.
                                'vcompression_method/'.
                                'vlast_mod_time/'.
                                'vlast_mod_date/'.
                                'Vcrc32/'.
                                'Vcompressed_size/'.
                                'Vuncompressed_size/'.
                                'vname_length/'.
                                'vextra_length/'.
                                'vcomment_length/'.
                                'vdisk_number/'.
                                'vinternal_attributes/'.
                                'Vexternal_attributes/'.
                                'Voffset',
                                $header);

         // Valid header?
         if($header_info['header'] != 33639248)
           return false;

         // New position
         $pos += 46;

         // Read out the file name
         $header_info['name'] = substr($cdr, $pos, $header_info['name_length']);

         // New position
         $pos += $header_info['name_length'];

         // Read out the extra stuff
         $header_info['extra'] = substr($cdr, $pos, $header_info['extra_length']);

         // New position
         $pos += $header_info['extra_length'];

         // Read out the comment
         $header_info['comment'] = substr($cdr, $pos, $header_info['comment_length']);

         // New position
         $pos += $header_info['comment_length'];

         // Append this file/dir to the entry list
         $entries[] = $header_info;
       }

       // Check whether all entries where read sucessfully
       if(count($entries) != $cd_entries)
         return false;

       // Handle files/dirs
       foreach($entries as $entry) {
         // Is a dir?
         if($entry['external_attributes'] & 16) {
           $this->add_dir($entry['name']);
           continue;
         }

         // Get local file header
         $header = substr($string, $entry['offset'], 30);

         // Unpack the header information
         $header_info = @unpack('Vheader/'.
                                'vversion_needed/'.
                                'vgeneral_purpose/'.
                                'vcompression_method/'.
                                'vlast_mod_time/'.
                                'vlast_mod_date/'.
                                'Vcrc32/'.
                                'Vcompressed_size/'.
                                'Vuncompressed_size/'.
                                'vname_length/'.
                                'vextra_length',
                                $header);

         // Valid header?
         if($header_info['header'] != 67324752)
           return false;

         // Get content start position
         $start = $entry['offset'] + 30 + $header_info['name_length'] + $header_info['extra_length'];

         // Get the compressed data
         $data = substr($string, $start, $header_info['compressed_size']);

         // Detect compression type
         switch($header_info['compression_method']) {
           // No compression
           case 0:
             // Ne decompression needed
             $content = $data;
             break;

           // Gzip
           case 8:
             if(!function_exists('gzinflate'))
               return false;

             // Uncompress data
             $content = gzinflate($data);
             break;

           // Bzip2
           case 12:
             if(!function_exists('bzdecompress'))
               return false;

             // Decompress data
             $content = bzdecompress($data);
             break;

           // Compression not supported -> error
           default:
             return false;
         }

         // Try to add file
         if(!$this->add_file($entry['name'], $content))
           return false;
       }

       return true;
     }
   }

   function &byref() {
       $x = array();
       return $x;
   }

   // Test highlighting of magic methods and variables
   class MagicClass {
     public $magic_str;
     public $ordinary_str;

     public function __construct($some_var) {
       $this->magic_str = __FILE__;
       $this->ordinary_str = $some_var;
     }

     public function __toString() {
       return $this->magic_str;
     }

     public function nonMagic() {
       return $this->ordinary_str;
     }
   }

   $magic = new MagicClass(__DIR__);
   __toString();
   $magic->nonMagic();
   $magic->__toString();

        echo <<<EOF

        Test the heredocs...

        EOF;

   echo <<<"some_delimiter"
   more heredoc testing
   continues on this line
   some_delimiter;

   ?>


From highlightjs.org::

   <?php

   require_once 'Zend/Uri/Http.php';

   namespace Location\Web;

   interface Factory
   {
       static function _factory();
   }

   abstract class URI extends BaseURI implements Factory
   {
       abstract function test();

       public static $st1 = 1;
       const ME = "Yo";
       var $list = NULL;
       private $var;

       /**
        * Returns a URI
        *
        * @return URI
        */
       static public function _factory($stats = array(), $uri = 'http')
       {
           echo __METHOD__;
           $uri = explode(':', $uri, 0b10);
           $schemeSpecific = isset($uri[1]) ? $uri[1] : '';
           $desc = 'Multi
       line description';

           // Security check
           if (!ctype_alnum($scheme)) {
               throw new Zend_Uri_Exception('Illegal scheme');
           }

           $this->var = 0 - self::$st;
           $this->list = list(Array("1"=> 2, 2=>self::ME, 3 => \Location\Web\URI::class));

           return [
               'uri'   => $uri,
               'value' => null,
           ];
       }
   }

From somewhere::

   <?php

   echo URI::ME . URI::$st1;

   __halt_compiler () ; datahere
   datahere
   datahere */
   datahere




Apache
======

.. highlight:: apache

::

   # rewrite`s rules for wordpress pretty url
   LoadModule rewrite_module  modules/mod_rewrite.so
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule . index.php [NC,L]

   ExpiresActive On
   ExpiresByType application/x-javascript  "access plus 1 days"

   Order Deny,Allow
   Allow from All

   <Location /maps/>
     RewriteMap map txt:map.txt
     RewriteMap lower int:tolower
     RewriteCond %{REQUEST_URI} ^/([^/.]+)\.html$ [NC]
     RewriteCond ${map:${lower:%1}|NOT_FOUND} !NOT_FOUND
     RewriteRule .? /index.php?q=${map:${lower:%1}} [NC,L]
   </Location>


Bash
====

.. highlight:: bash

::

   #!/bin/bash

   ###### BEGIN CONFIG
   ACCEPTED_HOSTS="/root/.hag_accepted.conf"
   BE_VERBOSE=false
   ###### END CONFIG

   if [ "$UID" -ne 0 ]
   then
    echo "Superuser rights is required"
    echo 'Printing the # sign'
    exit 2
   fi

   if test $# -eq 0
   then
   elif test [ $1 == 'start' ]
   else
   fi

   genApacheConf(){
    if [[ "$2" = "www" ]]
    then
     full_domain=$1
    else
     full_domain=$2.$1
    fi
    host_root="${APACHE_HOME_DIR}$1/$2/$(title)"
    echo -e "# Host $1/$2 :"
   }



.. code-block:: text

   ###
   CoffeeScript Compiler v1.2.0
   Released under the MIT License
   ###

   OPERATOR = /// ^ (
   ?: [-=]>             # function
   ) ///




C++
===

.. highlight:: c++

::

   #include <iostream>
   #define IABS(x) ((x) < 0 ? -(x) : (x))

   int main(int argc, char *argv[]) {

     /* An annoying "Hello World" example */
     for (auto i = 0; i < 0xFFFF; i++)
       cout << "Hello, World!" << endl;

     char c = '\n';
     unordered_map <string, vector<string> > m;
     m["key"] = "\\\\"; // this is an error

     return -2e3 + 12l;
   }


C#
==

.. highlight:: csharp

::

   using System;

   #pragma warning disable 414, 3021

   public class Program
   {
       /// <summary>The entry point to the program.</summary>
       public static int Main(string[] args)
       {
           Console.WriteLine("Hello, World!");
           string s = @"This
   ""string""
   spans
   multiple
   lines!";

           dynamic x = new ExpandoObject();
           x.MyProperty = 2;

           return 0;
       }
   }

   async Task<int> AccessTheWebAsync()
   {
       // ...
       string urlContents = await getStringTask;
       return urlContents.Length;
   }

   internal static void ExceptionFilters()
   {
     try
     {
         throw new Exception();
     }
     catch (Exception e) when (e.Message == "My error") { }
   }



CSS
===

.. highlight:: css

::

   @media screen and (-webkit-min-device-pixel-ratio: 0) {
     body:first-of-type pre::after {
       content: 'highlight: ' attr(class);
     }
     body {
       background: linear-gradient(45deg, blue, red);
     }
   }

   @import url('print.css');
   @page:right {
    margin: 1cm 2cm 1.3cm 4cm;
   }

   @font-face {
     font-family: Chunkfive; src: url('Chunkfive.otf');
   }

   div.text,
   #content,
   li[lang=ru] {
     font: Tahoma, Chunkfive, sans-serif;
     background: url('hatch.png') /* wtf? */;  color: #F0F0F0 !important;
     width: 100%;
   }



Diff
====

.. highlight:: diff

::

   Index: languages/ini.js
   ===================================================================
   --- languages/ini.js    (revision 199)
   +++ languages/ini.js    (revision 200)
   @@ -1,8 +1,7 @@
    hljs.LANGUAGES.ini =
    {
      case_insensitive: true,
   -  defaultMode:
   -  {
   +  defaultMode: {
        contains: ['comment', 'title', 'setting'],
        illegal: '[^\\s]'
      },

   *** /path/to/original timestamp
   --- /path/to/new      timestamp
   ***************
   *** 1,3 ****
   --- 1,9 ----
   + This is an important
   + notice! It should
   + therefore be located at
   + the beginning of this
   + document!

   ! compress the size of the
   ! changes.

     It is important to spell




Http
====

.. highlight:: http

::

   POST /task?id=1 HTTP/1.1
   Host: example.org
   Content-Type: application/json; charset=utf-8
   Content-Length: 19

   {"status": "ok", "extended": true}


Ini
===

.. highlight:: ini

::

   ;Settings relating to the location and loading of the database
   [Database]
   ProfileDir=.
   ShowProfileMgr=smart
   Profile1_Name[] = "\|/_-=MegaDestoyer=-_\|/"
   DefaultProfile=True
   AutoCreate = no

   [AutoExec]
   use-prompt="prompt"
   Glob=autoexec_*.ini
   AskAboutIgnoredPlugins=0


Java
====

.. highlight:: java

::

   /**
    * @author John Smith <john.smith@example.org>
    * @version 1.0
    */
   package l2f.gameserver.model;

   import java.util.ArrayList;

   public abstract class L2Character extends L2Object {
     public static final Short ABNORMAL_EFFECT_BLEEDING = 0x0_0_0_1; // not sure

     public void moveTo(int x, int y, int z) {
       _ai = null;
       _log.warning("Should not be called");
       if (1 > 5) {
         return;
       }
     }

     /** Task of AI notification */
     @SuppressWarnings( { "nls", "unqualified-field-access", "boxing" })
     public class NotifyAITask implements Runnable {
       private final CtrlEvent _evt;

       List<String> mList = new ArrayList<String>()

       public void run() {
         try {
           getAI().notifyEvent(_evt, _evt.class, null);
         } catch (Throwable t) {
           t.printStackTrace();
         }
       }
     }
   }



Javascript
==========

.. highlight:: javascript

::

   var makeNoise = function() {
     console.log("Pling!");
   };

   makeNoise();
   // → Pling!

   var power = function(base, exponent) {
     var result = 1;
     for (var count = 0; count < exponent; count++)
       result *= base;
     return result;
   };

   console.log(power(2, 10));
   // → 1024


JSON
====

.. highlight:: json

::

   [
     {
       "title": "apples",
       "count": [12000, 20000],
       "description": {"text": "...", "sensitive": false}
     },
     {
       "title": "oranges",
       "count": [17500, null],
       "description": {"text": "...", "sensitive": false}
     }
   ]


Makefile
========

.. highlight:: makefile

::

   # Makefile

   BUILDDIR      = _build
   EXTRAS       ?= $(BUILDDIR)/extras

   .PHONY: main clean

   main:
      @echo "Building main facility..."
      build_main $(BUILDDIR)

   clean:
      rm -rf $(BUILDDIR)/*


Markdown
========

.. highlight:: markdown

Let's see what happens with lexer `markdown`::

   # hello world

   you can write text [with links](https://example.org) inline or [link references][1].

   * one _thing_ has *em*phasis
   * two __things__ are **bold**

   [1]: https://example.org

   ---

   hello world
   ===========

   <this_is inline="xml"></this_is>

   > markdown is so cool

       so are code segments

   1. one thing (yeah!)
   2. two thing `i can write code`, and `more` wipee!


Nginx
=====

.. highlight:: nginx

::

   user  www www;
   worker_processes  2;
   pid /var/run/nginx.pid;
   error_log  /var/log/nginx.error_log  debug | info | notice | warn | error | crit;

   events {
       connections   2000;
       use kqueue | rtsig | epoll | /dev/poll | select | poll;
   }

   http {
       log_format main      '$remote_addr - $remote_user [$time_local] '
                            '"$request" $status $bytes_sent '
                            '"$http_referer" "$http_user_agent" '
                            '"$gzip_ratio"';

       send_timeout 3m;
       client_header_buffer_size 1k;

       gzip on;
       gzip_min_length 1100;

       #lingering_time 30;

       server {
           server_name   one.example.org  www.one.example.org;
           access_log   /var/log/nginx.access_log  main;

           rewrite (.*) /index.php?page=$1 break;

           location / {
               proxy_pass         http://127.0.0.1/;
               proxy_redirect     off;
               proxy_set_header   Host             $host;
               proxy_set_header   X-Real-IP        $remote_addr;
               charset            koi8-r;
           }

           location /api/ {
               fastcgi_pass 127.0.0.1:9000;
           }

           location ~* \.(jpg|jpeg|gif)$ {
               root         /spool/www;
           }
       }
   }


Objective C
===========

.. highlight:: objectivec

::

   #import <UIKit/UIKit.h>
   #import "Dependency.h"

   @protocol WorldDataSource
   @optional
   - (NSString*)worldName;
   @required
   - (BOOL)allowsToLive;
   @end

   @interface Test : NSObject <HelloDelegate, WorldDataSource> {
     NSString *_greeting;
   }

   @property (nonatomic, readonly) NSString *greeting;
   - (IBAction) show;
   @end

   @implementation Test

   @synthesize test=_test;

   + (id) test {
     return [self testWithGreeting:@"Hello, world!\nFoo bar!"];
   }

   + (id) testWithGreeting:(NSString*)greeting {
     return [[[self alloc] initWithGreeting:greeting] autorelease];
   }

   - (id) initWithGreeting:(NSString*)greeting {
     if ( (self = [super init]) ) {
       _greeting = [greeting retain];
     }
     return self;
   }

   - (void) dealloc {
     [_greeting release];
     [super dealloc];
   }

   @end


Perl
====

.. highlight:: perl

::

   # loads object
   sub load
   {
     my $flds = $c->db_load($id,@_) || do {
       Carp::carp "Can`t load (class: $c, id: $id): '$!'"; return undef
     };
     my $o = $c->_perl_new();
     $id12 = $id / 24 / 3600;
     $o->{'ID'} = $id12 + 123;
     #$o->{'SHCUT'} = $flds->{'SHCUT'};
     my $p = $o->props;
     my $vt;
     $string =~ m/^sought_text$/;
     $items = split //, 'abc';
     $string //= "bar";
     for my $key (keys %$p)
     {
       if(${$vt.'::property'}) {
         $o->{$key . '_real'} = $flds->{$key};
         tie $o->{$key}, 'CMSBuilder::Property', $o, $key;
       }
     }
     $o->save if delete $o->{'_save_after_load'};

     # GH-117
     my $g = glob("/usr/bin/*");

     return $o;
   }

   =head1 NAME
   POD till the end of file



Python
======

.. highlight:: python

::

   @requires_authorization
   def somefunc(param1='', param2=0):
       r'''A docstring'''
       if param1 > param2: # interesting
           print 'Gre\'ater'
       return (param2 - param1 + 1 + 0b10l) or None

   class SomeClass:
       pass

::

   >>> message = '''interpreter
   ... prompt'''


Ruby
====

.. highlight:: ruby

::

   class A < B; def self.create(object = User) object end end
   class Zebra; def inspect; "X#{2 + self.object_id}" end end

   module ABC::DEF
     include Comparable

     # @param test
     # @return [String] nothing
     def foo(test)
       Thread.new do |blockvar|
         ABC::DEF.reverse(:a_symbol, :'a symbol', :<=>, 'test' + ?\012)
         answer = valid?4 && valid?CONST && ?A && ?A.ord
       end.join
     end

     def [](index) self[index] end
     def ==(other) other == self end
   end

   class Car < ActiveRecord::Base
     has_many :wheels, class_name: 'Wheel', foreign_key: 'car_id'
     scope :available, -> { where(available: true) }
   end

   hash = {1 => 'one', 2 => 'two'}

   2.0.0p0 :001 > ['some']
    => ["some"]



SQL
===

.. highlight:: sql

::

   BEGIN;
   CREATE TABLE "topic" (
       -- This is the greatest table of all time
       "id" serial NOT NULL PRIMARY KEY,
       "forum_id" integer NOT NULL,
       "subject" varchar(255) NOT NULL -- Because nobody likes an empty subject
   );
   ALTER TABLE "topic" ADD CONSTRAINT forum_id FOREIGN KEY ("forum_id") REFERENCES "forum" ("id");

   -- Initials
   insert into "topic" ("forum_id", "subject") values (2, 'D''artagnian');

   select /* comment */ count(*) from cicero_forum;

   -- this line lacks ; at the end to allow people to be sloppy and omit it in one-liners
   /*
   but who cares?
   */
   COMMIT



Html
====

.. highlight:: html

::

   <!DOCTYPE html>
   <title>Title</title>

   <style>body {width: 500px;}</style>

   <script type="application/javascript">
     function $init() {return true;}
   </script>

   <body>
     <p checked class="title" id='title'>Title</p>
     <!-- here goes the rest of the page -->
   </body>


Xml
===

.. highlight:: xml

::

   <?xml version="1.0"?>
   <response value="ok" xml:lang="en">
     <text>Ok</text>
     <comment html_allowed="true"/>
     <ns1:description><![CDATA[
     CDATA is <not> magical.
     ]]></ns1:description>
     <a></a> <a/>
   </response>
