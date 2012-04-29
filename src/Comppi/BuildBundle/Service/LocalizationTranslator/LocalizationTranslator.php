<?php

namespace Comppi\BuildBundle\Service\LocalizationTranslator;

class LocalizationTranslator
{
    private $localizations = array (
        'GO:0005575' => array(
        	'GO:0005623' => array(
        		'GO:0005622' => array(
        			'GO:0005737' => array(
        				'GO:0005811',
        				'GO:0005815' => array(
        					'GO:0005932'
        				),
        				'GO:0005938' => array(
        					'GO:0000145',
        					'GO:0000133'
        				),
        			),
        			'GO:0016234' => array(
        				'GO:0016235'
        			),
        			'GO:0005930',
        			'GO:0000307',
        			'GO:0009368',
        			'GO:0000178',
        			'GO:0045252',
        			'GO:0030529',
        			'GO:0030880',
        			'GO:0000151',
        			'GO:0017122',
        			'GO:0000131',
        			'GO:0016234',
        			'GO:0043229' => array(
        				'GO:0043231' => array(
        					'GO:0005739' => array(
        						'GO:0031966' => array(
        							'GO:0005743',
        							'GO:0005741'
        						),
        					),
        					'GO:0005634' => array(
        						'GO:0005635',
        						'GO:0031981' => array(
        							'GO:0016604' => array(
        								'GO:0016607'
        							),
        						),
        						'GO:0005730',
        						'GO:0031965' => array(
        							'GO:0005637' => array(
        								'GO:0031229' => array(
        									'GO:0005639'
        								),
        							),
        						),
        						'GO:0031533'
        					),
        					'GO:0005783' => array(
        						'GO:0005789' => array(
        							'GO:0042406',
        							'GO:0031227' => array(
        								'GO:0030176'
        							),
        						),
        					),
        					'GO:0005793',
        					'GO:0005794' => array(
        						'GO:0031985' => array(
        							'GO:0000137',
        							'GO:0000138',
        							'GO:0005797'
        						),
        						'GO:0005802',
        						'GO:0000139' => array(
        							'GO:0031228' => array(
        								'GO:0030173'
        							),
        						),
        					),
        					'GO:0005768' => array(
        						'GO:0005769',
        						'GO:0005770'
        					),
        					'GO:0005773' => array(
        						'GO:0005774',
        						'GO:0000323' => array(
        							'GO:0005764' => array(
        								'GO:0005765'
        							),
        						),
        						'GO:0000306'
        					),
        					'GO:0042579' => array(
        						'GO:0005777' => array(
        							'GO:0005778'
        						),
        					),
        					'GO:0031410' => array(
        						'GO:0016023' => array(
        							'GO:0048770' => array(
        								'GO:0042470'
        							),
        							'GO:0030141',
        							'GO:0030135' => array(
        								'GO:0030136' => array(
        									'GO:0008021' => array(
        										'GO:0030672' => array(
        											'GO:0030285'
        										),
        									),
        								),
        							),
        							'GO:0030133'
        						),
        					),
        				),
        				'GO:0043232' => array(
        					'GO:0009295',
        					'GO:0005856' => array(
        						'GO:0005940',
        						'GO:0015629',
        						'GO:0045111',
        						'GO:0015630' => array(
        							'GO:0005815' => array(
        								'GO:0005813'
        							),
        						),
        					),
        					'GO:0005694'
        				),
        			),
        		),
        		'GO:0071944' => array(
        			'GO:0005618'
        		),
        		'GO:0030427' => array(
        			'GO:0005935',
        			'GO:0005934'
        		),
        		'GO:0030428',
        		'GO:0042995' => array(
        			'GO:0005929',
        			'GO:0019861'
        		),
        		'GO:0070938',
        		'GO:0012505'
        	),
        	'GO:0005576',
        	'GO:0016020' => array(
        		'GO:0045281',
        		'GO:0070469' => array(
        			'GO:0045271',
        			'GO:0045273',
        			'GO:0045275',
        			'GO:0045277'
        		),
        		'GO:0046930',
        		'GO:0016469' => array(
        			'GO:0016469'
        		),
        		'GO:0046696',
        		'GO:0005886' => array(
        			'GO:0016324',
        			'GO:0016323' => array(
        				'GO:0005924' => array(
        					'GO:0005925'
        				),
        			),
        		),
        		'GO:0031224' => array(
        			'GO:0016021' => array(
        				'GO:0034702' => array(
        					'GO:0034703' => array(
        						'GO:0017071'
        					),
        				),
        			),
        		),
        	),
        	'GO:0043226' => array(
        		'GO:0031090' => array(
        			'GO:0019866'
        		),
        		'GO:0031982'
        	),
        	'GO:0030054' => array(
        		'GO:0005911' => array(
        			'GO:0070160' => array(
        				'GO:0005923'
        			),
        		),
        	),
        ),
        'secretory_pathway'
    );
    
    private $localizationTree = array();
    private $localizationToId;
    
    public function __construct() {
        $this->buildTree('Protein', $this->localizations, 0);
        $this->initLocalizationToIdMap();
    }
    
    private function buildTree($localization, $node, $id) {
        if (is_array($node)) {
            $nodeId = $id;
            $this->localizationTree[$id] = array(
                'name' => $localization
            );
            $id++;
            
            foreach ($node as $key => $value) {
                $id = $this->buildTree($key, $value, $id);
            }
            
            $this->localizationTree[$nodeId]['sid'] = $id;
            
            return $id+1;
            
        } else {
            $this->localizationTree[$id] = array(
                'name' => $node,
                'sid' => $id+1
            );
            
            return $id+2;
        }
    }
    
    private function initLocalizationToIdMap() {
        foreach ($this->localizationTree as $id => $node) {
            $this->localizationToId[$node['name']] = $id;
        }
    }
    
    public function getIdByLocalization($localization) {
        if (isset($this->localizationToId[$localization])) {
            return $this->localizationToId[$localization];
        } else {
            throw new \InvalidArgumentException("Localization ('".$localization."') not found in tree");
        }
    }
    
    public function getSecondaryIdByLocalization($localization) {
        throw new \BadMethodCallException("getSecondaryIdByLocalization method not implemented");
    }
    
    public function getLocalizationById($id) {
        if (isset($this->localizationTree[$id])) {
            return $this->localizationTree[$id];
        } else {
           throw new \InvalidArgumentException("Given id ('".$id."') is not valid primary localization id"); 
        }
    }
}