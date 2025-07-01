<?php
/**
 * Ok, glad you are here
 * first we get a config instance, and set the settings
 * $config = HTMLPurifier_Config::createDefault();
 * $config->set('Core.Encoding', $this->config->get('purifier.encoding'));
 * $config->set('Cache.SerializerPath', $this->config->get('purifier.cachePath'));
 * if ( ! $this->config->get('purifier.finalize')) {
 *     $config->autoFinalize = false;
 * }
 * $config->loadArray($this->getConfig());
 *
 * You must NOT delete the default settings
 * anything in settings should be compacted with params that needed to instance HTMLPurifier_Config.
 *
 * @link http://htmlpurifier.org/live/configdoc/plain.html
 */

return [
    'encoding'           => 'UTF-8',
    'finalize'           => true,
    'ignoreNonStrings'   => false,
    'cachePath'          => storage_path('app/purifier'),
    'cacheFileMode'      => 0755,
    'settings'      => [
        'default' => [
            'HTML.Allowed' => 'p,b,i,strong,em,u,a[href|title|target|download],ul,ol,li,h1,h2,h3,h4,h5,blockquote,img[src|alt|width|height|class],hr,br,iframe[src|width|height|frameborder|allowfullscreen],div[class|style],span[class|style],video[src|controls|width|height],source[src|type],table,tr,td[colspan|rowspan],th[colspan|rowspan]',
            'HTML.SafeIframe' => true,
            'URI.SafeIframeRegexp' => '%^(https?:)?//(www\.youtube\.com/embed/|player\.vimeo\.com/video/)%',
            'AutoFormat.RemoveEmpty' => true,
            'CSS.AllowedProperties' => 'font,font-size,font-weight,font-style,font-family,text-decoration,padding-left,color,background-color,text-align,width,height,margin,margin-left,margin-right,border,border-radius,padding,display,flex,align-items,justify-content,gap',
            'AutoFormat.AutoParagraph' => true,
            'AutoFormat.Linkify' => true,
        ],
        'test'    => [
            'Attr.EnableID' => 'true',
        ],
        "youtube" => [
            "HTML.SafeIframe"      => 'true',
            "URI.SafeIframeRegexp" => "%^(http://|https://|//)(www.youtube.com/embed/|player.vimeo.com/video/)%",
        ],
        // Configuração específica para conteúdo de notícias com documentos
            'noticias' => [
                'HTML.Allowed' => 'p,b,i,strong,em,u,a[href|title|target|download|rel|onmouseover|onmouseout],ul,ol,li,h1,h2,h3,h4,h5,h6,blockquote,img[src|alt|width|height|class],hr,br,iframe[src|width|height|frameborder|allowfullscreen],div[class|style],span[class|style],video[src|controls|width|height],source[src|type],table,tr,td[colspan|rowspan],th[colspan|rowspan]',
                'HTML.SafeIframe' => true,
                'URI.SafeIframeRegexp' => '%^(https?:)?//(www\.youtube\.com/embed/|player\.vimeo\.com/video/)%',
                'AutoFormat.RemoveEmpty' => true,
                'CSS.AllowedProperties' => 'font,font-size,font-weight,font-style,font-family,text-decoration,padding-left,color,background-color,background,text-align,width,height,margin,margin-left,margin-right,margin-bottom,margin-top,border,border-radius,padding,display,flex,flex-direction,align-items,justify-content,gap,transition,transform,overflow,text-overflow,white-space,flex-shrink,flex-grow,min-width,user-select,cursor,border-color',
                'AutoFormat.AutoParagraph' => true,
                'AutoFormat.Linkify' => true,
                'Attr.AllowedClasses' => 'document-attachment',
                'HTML.TargetBlank' => true,
                'CSS.AllowTricky' => true,
                'CSS.Trusted' => true,
            ],
        'custom_definition' => [
            'id'  => 'html5-definitions',
            'rev' => 1,
            'debug' => false,
            'elements' => [
                // http://developers.whatwg.org/sections.html
                ['section', 'Block', 'Flow', 'Common'],
                ['nav',     'Block', 'Flow', 'Common'],
                ['article', 'Block', 'Flow', 'Common'],
                ['aside',   'Block', 'Flow', 'Common'],
                ['header',  'Block', 'Flow', 'Common'],
                ['footer',  'Block', 'Flow', 'Common'],
				
				// Content model actually excludes several tags, not modelled here
                ['address', 'Block', 'Flow', 'Common'],
                ['hgroup', 'Block', 'Required: h1 | h2 | h3 | h4 | h5 | h6', 'Common'],
				
				// http://developers.whatwg.org/grouping-content.html
                ['figure', 'Block', 'Optional: (figcaption, Flow) | (Flow, figcaption) | Flow', 'Common'],
                ['figcaption', 'Inline', 'Flow', 'Common'],
				
				// http://developers.whatwg.org/the-video-element.html#the-video-element
                ['video', 'Block', 'Optional: (source, Flow) | (Flow, source) | Flow', 'Common', [
                    'src' => 'URI',
					'type' => 'Text',
					'width' => 'Length',
					'height' => 'Length',
					'poster' => 'URI',
					'preload' => 'Enum#auto,metadata,none',
					'controls' => 'Bool',
                ]],
                ['source', 'Block', 'Flow', 'Common', [
					'src' => 'URI',
					'type' => 'Text',
                ]],

				// http://developers.whatwg.org/text-level-semantics.html
                ['s',    'Inline', 'Inline', 'Common'],
                ['var',  'Inline', 'Inline', 'Common'],
                ['sub',  'Inline', 'Inline', 'Common'],
                ['sup',  'Inline', 'Inline', 'Common'],
                ['mark', 'Inline', 'Inline', 'Common'],
                ['wbr',  'Inline', 'Empty', 'Core'],
				
				// http://developers.whatwg.org/edits.html
                ['ins', 'Block', 'Flow', 'Common', ['cite' => 'URI', 'datetime' => 'CDATA']],
                ['del', 'Block', 'Flow', 'Common', ['cite' => 'URI', 'datetime' => 'CDATA']],
            ],
            'attributes' => [
                ['iframe', 'allowfullscreen', 'Bool'],
                ['table', 'height', 'Text'],
                ['td', 'border', 'Text'],
                ['th', 'border', 'Text'],
                ['tr', 'width', 'Text'],
                ['tr', 'height', 'Text'],
                ['tr', 'border', 'Text'],
                ['a', 'download', 'Text'],
                ['a', 'target', 'Enum#_blank,_self,_target,_top'],
                ['a', 'onmouseover', 'Text'],
                ['a', 'onmouseout', 'Text'],
                ['div', 'class', 'Text'],
                ['div', 'style', 'Text'],
                ['span', 'class', 'Text'],
                ['span', 'style', 'Text'],
            ],
        ],
        'custom_attributes' => [
            ['a', 'target', 'Enum#_blank,_self,_target,_top'],
            ['a', 'download', 'Text'],
        ],
        'custom_elements' => [
            ['u', 'Inline', 'Inline', 'Common'],
        ],
    ],

];