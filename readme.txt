Search Engine Highlight Readme

brian suda
http://suda.co.uk

There are usage instruction in the PHP file about how to setup the function. More information about the PHP functions can be found at PHP.net, you can also download php and other modules from there. This will require some libraries that are not always installed by default, my hosting provides has these functions available, your's may not. Specifically, look out for % (mod), i am using '%' you may need to convert this to bcmod(). Also, I use mb_strlen() for UTF-8 strings. This could be substitued for iconv_strlen() in PHP5.

I have had this running on a version of PHP5 and PHP4.3

UPDATES
This file maybe updated with faster more efficient regular expressions or other functions to improve the highlighting or to fix bugs or add additional search engine query strings, if so the official webpage for the SEHL file is:
http://suda.co.uk/project/SEHL/

COPYRIGHT
This is distributed under the GNU Lesser General Public License. For more information please visit: http://www.gnu.org/licenses/lgpl.html