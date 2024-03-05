
.. include:: /Includes.rst.txt


=========================
raw
=========================

.. attention::

   Keep this page!

   If the raw directive is disabled it verifies that fact. Otherwise it
   demonstrates what the raw directive can do.


There are valid use cases for the 'raw' directive. The configuration of the
TYPO3 Docker rendering container has this directive disabled as it
represents a security hole. So this might suggest that it's useless to keep
example showing the raw directive. However, this is not quite true.

We'll keep examples for the raw directive here because it is an easy check
to verify that the directive is really turn off.

Secondly there'll be an option for the jobfile.json to enable the raw directive
in case of local renderings.



Color Constants
===============

.. raw:: html

   <div class="wy-table-responsive">
     <table border="1" class="docutils">
       <colgroup>
         <col width="25%">
         <col width="25%">
         <col width="25%">
         <col width="25%">
       </colgroup>
       <thead valign="bottom">
         <tr class="row-odd">
           <th class="head">Column A</th>
           <th class="head">Column B</th>
           <th class="head">Column C</th>
           <th class="head">Column D</th>
         </tr>
       </thead>
       <tbody valign="top">
         <tr class="XXXrow-even">
           <td class="bg-typo3-key-color"           >typo3-key-color</td>
           <td class="bg-typo3-support-orange-dark" >typo3-support-orange-dark</td>
           <td class="bg-typo3-support-orange-light">typo3-support-orange-light</td>
           <td class="bg-typo3-marker-orange"       >typo3-marker-orange</td>
         </tr>
         <tr class="XXXrow-odd">
           <td class="bg-typo3-dark-grey"  >typo3-dark-grey  </td>
           <td class="bg-typo3-mid-grey"   >typo3-mid-grey   </td>
           <td class="bg-typo3-light-grey" >typo3-light-grey </td>
           <td class="bg-typo3-marker-grey">typo3-marker-grey</td>
         </tr>
         <tr class="XXXrow-even">
           <td class="bg-typo3-message-valid"      >typo3-message-valid      </td>
           <td class="bg-typo3-message-error"      >typo3-message-error      </td>
           <td class="bg-typo3-message-warning"    >typo3-message-warning    </td>
           <td class="bg-typo3-message-information">typo3-message-information</td>
         </tr>
         <tr class="XXXrow-odd">
           <td class="bg-black"      >black      </td>
           <td class="bg-gray-darker">gray-darker</td>
           <td class="bg-gray-dark"  >gray-dark  </td>
           <td class="bg-gray"       >gray       </td>
         </tr>
         <tr class="XXXrow-even">
           <td class="bg-gray"        >gray        </td>
           <td class="bg-gray-light"  >gray-light  </td>
           <td class="bg-gray-lighter">gray-lighter</td>
           <td class="bg-white"       >white       </td>
         </tr>
         <tr class="">
           <td class="bg-green"   >green   </td>
           <td class="bg-offgreen">offgreen</td>
           <td class="bg-blue"    >blue    </td>
           <td class="bg-purple"  >purple  </td>
         </tr>
         <tr class="">
           <td class="bg-cobalt">cobalt</td>
           <td class="bg-yellow">yellow</td>
           <td class="bg-orange">orange</td>
           <td class="bg-red"   >red   </td>
         </tr>
         <tr class="">
           <td class="bg-shell"                 >shell                 </td>
           <td class="bg-text-code-border-color">text-code-border-color</td>
           <td class=""></td>
           <td class=""></td>
         </tr>
         <tr class="">
           <td class="bg-text-color"                >text-color                </td>
           <td class="bg-text-invert"               >text-invert               </td>
           <td class="bg-text-code-color"           >text-code-color           </td>
           <td class="bg-text-code-background-color">text-code-background-color</td>
         </tr>
         <tr class="">
           <td class="bg-text-dark"   >text-dark   </td>
           <td class="bg-text-medium" >text-medium </td>
           <td class="bg-text-light"  >text-light  </td>
           <td class="bg-text-lighter">text-lighter</td>
         </tr>
         <tr class="">
           <td class="bg-table-background-color">table-background-color</td>
           <td class="bg-table-border-color"    >table-border-color    </td>
           <td class="bg-table-stripe-color"    >table-stripe-color    </td>
           <td class="bg-table-bg-hover-color"  >table-bg-hover-color  </td>
         </tr>
         <tr class="">
           <td class="bg-table-head-background-color">table-head-background-color</td>
           <td class=""></td>
           <td class=""></td>
           <td class=""></td>
         </tr>
         <tr class="">
           <td class="bg-input-text-color"      >input-text-color      </td>
           <td class="bg-input-background-color">input-background-color</td>
           <td class="bg-input-border-color"    >input-border-color    </td>
           <td class="bg-input-shadow-color"    >input-shadow-color    </td>
         </tr>
         <tr class="">
           <td class="bg-input-focus-color">input-focus-color</td>
           <td class=""></td>
           <td class=""></td>
           <td class=""></td>
         </tr>
         <tr class="">
           <td class="bg-link-color"        >link-color        </td>
           <td class="bg-link-color-visited">link-color-visited</td>
           <td class="bg-link-color-hover"  >link-color-hover  </td>
           <td class="bg-link-color-alt"    >link-color-alt    </td>
         </tr>
         <tr class="">
           <td class=""></td>
           <td class=""></td>
           <td class=""></td>
           <td class=""></td>
         </tr>
         <tr class="">
           <td class="bg-menu-top-link-color"  >menu-top-link-color  </td>
           <td class="bg-menu-background-color">menu-background-color</td>
           <td class="bg-menu-logo-color"      >menu-logo-color      </td>
           <td class=""></td>
         </tr>
         <tr class="">
           <td class="bg-button-background-color"        >button-background-color        </td>
           <td class="bg-button-neutral-background-color">button-neutral-background-color</td>
           <td class="bg-spinner-color"                  >spinner-color                  </td>
           <td class=""></td>
         </tr>
        </tbody>
      </table>
    </div>


