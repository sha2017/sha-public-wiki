<?php
/**
 * Static functions for handling lists of values and labels.
 *
 * @author Yaron Koren
 * @file
 * @ingroup PF
 */

class PFValuesUtils {

	/**
	 * Helper function to handle getPropertyValues().
	 */
	public static function getSMWPropertyValues( $store, $subject, $propID, $requestOptions = null ) {
		// If SMW is not installed, exit out.
		if ( !class_exists( 'SMWDIWikiPage' ) ) {
			return array();
		}
		if ( is_null( $subject ) ) {
			$page = null;
		} else {
			$page = SMWDIWikiPage::newFromTitle( $subject );
		}
		$property = SMWDIProperty::newFromUserLabel( $propID );
		$res = $store->getPropertyValues( $page, $property, $requestOptions );
		$values = array();
		foreach ( $res as $value ) {
			if ( $value instanceof SMWDIUri ) {
				$values[] = $value->getURI();
			} elseif ( $value instanceof SMWDIWikiPage ) {
				$realValue = str_replace( '_', ' ', $value->getDBKey() );
				if ( $value->getNamespace() != 0 ) {
					$realValue = MWNamespace::getCanonicalName($value->getNamespace()) . ":$realValue";
				}
				$values[] = $realValue;
			} else {
				// getSortKey() seems to return the correct
				// value for all the other data types.
				$values[] = str_replace( '_', ' ', $value->getSortKey() );
			}
		}
		return $values;
	}

	/**
	 * Helper function - gets names of categories for a page;
	 * based on Title::getParentCategories(), but simpler
	 * - this function doubles as a function to get all categories on
	 * the site, if no article is specified
	 */
	public static function getCategoriesForPage( $title = null ) {
		$categories = array();
		$db = wfGetDB( DB_SLAVE );
		$conditions = null;
		if ( !is_null( $title ) ) {
			$titlekey = $title->getArticleID();
			if ( $titlekey == 0 ) {
				// Something's wrong - exit
				return $categories;
			}
			$conditions['cl_from'] = $titlekey;
		}
		$res = $db->select(
			'categorylinks',
			'DISTINCT cl_to',
			$conditions,
			__METHOD__
		);
		if ( $db->numRows( $res ) > 0 ) {
			while ( $row = $db->fetchRow( $res ) ) {
				$categories[] = $row['cl_to'];
			}
		}
		$db->freeResult( $res );
		return $categories;
	}

	/**
	 * This function, unlike the others, doesn't take in a substring
	 * because it uses the SMW data store, which can't perform
	 * case-insensitive queries; for queries with a substring, the
	 * function PFAutocompleteAPI::getAllValuesForProperty() exists.
	 */
	public static function getAllValuesForProperty( $property_name ) {
		global $wgPageFormsMaxAutocompleteValues;

		$store = PFUtils::getSMWStore();
		if ( $store == null ) {
			return array();
		}
		$requestoptions = new SMWRequestOptions();
		$requestoptions->limit = $wgPageFormsMaxAutocompleteValues;
		$values = self::getSMWPropertyValues( $store, null, $property_name, $requestoptions );
		sort( $values );
		return $values;
	}

	/**
	 * Used with the Cargo extension
	 */
	public static function getAllValuesForCargoField( $tableName, $fieldName ) {
		return self::getValuesForCargoField( $tableName, $fieldName );
	}

	/**
	 * Used with the Cargo extension
	 */
	public static function getValuesForCargoField( $tableName, $fieldName, $whereStr = null ) {
		global $wgPageFormsMaxLocalAutocompleteValues;

		// The limit should be greater than the maximum number of local
		// autocomplete values, so that form inputs also know whether
		// to switch to remote autocompletion.
		// (We increment by 10, to be on the safe side, since some values
		// can be null, etc.)
		$limitStr = max( 100, $wgPageFormsMaxLocalAutocompleteValues + 10);

		try {
			$sqlQuery = CargoSQLQuery::newFromValues( $tableName, $fieldName, $whereStr, $joinOnStr = null, $fieldName, $havingStr = null, $fieldName, $limitStr );
		} catch ( Exception $e ) {
			return array();
		}

		$queryResults = $sqlQuery->run();
		$values = array();
		// Field names starting with a '_' are special fields -
		// all other fields will have had their underscores
		// replaced with spaces in $queryResults.
		if ( $fieldName[0] == '_' ) {
			$fieldAlias = $fieldName;
		} else {
			$fieldAlias = str_replace( '_', ' ', $fieldName );
		}
		foreach ( $queryResults as $row ) {
			$values[] = $row[$fieldAlias];
		}
		return $values;
	}

	/**
	 * Get all the pages that belong to a category and all its
	 * subcategories, down a certain number of levels - heavily based on
	 * SMW's SMWInlineQuery::includeSubcategories()
	 */
	public static function getAllPagesForCategory( $top_category, $num_levels, $substring = null ) {
		if ( 0 == $num_levels ) {
			return $top_category;
		}
		global $wgPageFormsMaxAutocompleteValues, $wgPageFormsUseDisplayTitle;

		$db = wfGetDB( DB_SLAVE );
		$top_category = str_replace( ' ', '_', $top_category );
		$categories = array( $top_category );
		$checkcategories = array( $top_category );
		$pages = array();
		$sortkeys = array();
		for ( $level = $num_levels; $level > 0; $level-- ) {
			$newcategories = array();
			foreach ( $checkcategories as $category ) {
				$tables = array( 'categorylinks', 'page' );
				$columns = array( 'page_title', 'page_namespace' );
				$conditions = array();
				$conditions[] = 'cl_from = page_id';
				$conditions['cl_to'] = $category;
				if ( $wgPageFormsUseDisplayTitle ) {
					$tables['pp_displaytitle'] = 'page_props';
					$tables['pp_defaultsort'] = 'page_props';
					$columns['pp_displaytitle_value'] = 'pp_displaytitle.pp_value';
					$columns['pp_defaultsort_value'] = 'pp_defaultsort.pp_value';
					$join = array(
						'pp_displaytitle' => array(
							'LEFT JOIN', array(
								'pp_displaytitle.pp_page = page_id',
								'pp_displaytitle.pp_propname = "displaytitle"'
							)
						),
						'pp_defaultsort' => array(
							'LEFT JOIN', array(
								'pp_defaultsort.pp_page = page_id',
								'pp_defaultsort.pp_propname = "defaultsort"'
							)
						)
					);
					if ( $substring != null ) {
						$conditions[] = '(pp_displaytitle.pp_value IS NULL AND (' .
							self::getSQLConditionForAutocompleteInColumn( 'page_title', $substring ) .
							')) OR ' .
							self::getSQLConditionForAutocompleteInColumn( 'pp_displaytitle.pp_value', $substring ) .
							' OR page_namespace = ' . NS_CATEGORY;
					}
				} else {
					$join = array();
					if ( $substring != null ) {
						$conditions[] = self::getSQLConditionForAutocompleteInColumn( 'page_title', $substring ) . ' OR page_namespace = ' . NS_CATEGORY;
					}
				}
				$res = $db->select( // make the query
					$tables,
					$columns,
					$conditions,
					__METHOD__,
					$options = array(
						'ORDER BY' => 'cl_type, cl_sortkey',
						'LIMIT' => $wgPageFormsMaxAutocompleteValues
					),
					$join );
				if ( $res ) {
					while ( $res && $row = $db->fetchRow( $res ) ) {
						if ( !array_key_exists( 'page_title', $row ) ) {
							continue;
						}
						$page_namespace = $row['page_namespace'];
						$page_name = $row[ 'page_title' ];
						if ( $page_namespace == NS_CATEGORY ) {
							if ( !in_array( $page_name, $categories ) ) {
								$newcategories[] = $page_name;
							}
						} else {
							$cur_title = Title::makeTitleSafe( $page_namespace, $page_name );
							if ( is_null( $cur_title ) ) {
								// This can happen if it's
								// a "phantom" page, in a
								// namespace that no longer exists.
								continue;
							}
							$cur_value = PFUtils::titleString( $cur_title );
							if ( ! in_array( $cur_value, $pages ) ) {
								if ( array_key_exists( 'pp_displaytitle_value' , $row ) &&
									!is_null( $row[ 'pp_displaytitle_value' ] ) &&
									trim( str_replace( '&#160;', '', strip_tags( $row[ 'pp_displaytitle_value' ] ) ) ) !== '' ) {
									$pages[ $cur_value ] = htmlspecialchars_decode( $row[ 'pp_displaytitle_value'] );
								} else {
									$pages[ $cur_value ] = $cur_value;
								}
								if ( array_key_exists( 'pp_defaultsort_value' , $row ) &&
									!is_null( $row[ 'pp_defaultsort_value' ] ) ) {
									$sortkeys[ $cur_value ] = $row[ 'pp_defaultsort_value'];
								} else {
									$sortkeys[ $cur_value ] = $cur_value;
								}
							}
						}
					}
					$db->freeResult( $res );
				}
			}
			if ( count( $newcategories ) == 0 ) {
				array_multisort( $sortkeys, $pages );
				return $pages;
			} else {
				$categories = array_merge( $categories, $newcategories );
			}
			$checkcategories = array_diff( $newcategories, array() );
		}
		array_multisort( $sortkeys, $pages );
		return $pages;
	}

	public static function getAllPagesForConcept( $conceptName, $substring = null ) {
		global $wgPageFormsMaxAutocompleteValues, $wgPageFormsAutocompleteOnAllChars;

		$store = PFUtils::getSMWStore();
		if ( $store == null ) {
			return array();
		}

		$conceptTitle = Title::makeTitleSafe( SMW_NS_CONCEPT, $conceptName );

		if ( !is_null( $substring ) ) {
			$substring = strtolower( $substring );
		}

		// Escape if there's no such concept.
		if ( $conceptTitle == null || !$conceptTitle->exists() ) {
			return wfMessage( 'pf-missingconcept', wfEscapeWikiText( $conceptName ) );
		}

		global $wgPageFormsUseDisplayTitle;
		$conceptDI = SMWDIWikiPage::newFromTitle( $conceptTitle );
		$desc = new SMWConceptDescription( $conceptDI );
		$printout = new SMWPrintRequest( SMWPrintRequest::PRINT_THIS, "" );
		$desc->addPrintRequest( $printout );
		$query = new SMWQuery( $desc );
		$query->setLimit( $wgPageFormsMaxAutocompleteValues );
		$query_result = $store->getQueryResult( $query );
		$pages = array();
		$sortkeys = array();
		$titles = array();
		while ( $res = $query_result->getNext() ) {
			$page = $res[0]->getNextText( SMW_OUTPUT_WIKI );
			if ( $wgPageFormsUseDisplayTitle && class_exists( 'PageProps' ) ) {
				$title = Title::newFromText( $page );
				if ( !is_null( $title ) ) {
					$titles[] = $title;
				}
			} else {
				$pages[$page] = $page;
				$sortkeys[$page] = $page;
			}
		}

		if ( $wgPageFormsUseDisplayTitle && class_exists( 'PageProps' ) ) {
			$properties = PageProps::getInstance()->getProperties( $titles,
				array( 'displaytitle', 'defaultsort' ) );
			foreach ( $titles as $title ) {
				if ( array_key_exists( $title->getArticleID(), $properties ) ) {
					$titleprops = $properties[$title->getArticleID()];
					if ( array_key_exists( 'displaytitle', $titleprops ) &&
						trim( str_replace( '&#160;', '', strip_tags( $titleprops['displaytitle'] ) ) ) !== '' ) {
						$pages[$title->getPrefixedText()] = htmlspecialchars_decode( $titleprops['displaytitle'] );
					} else {
						$pages[$title->getPrefixedText()] = $title->getPrefixedText();
					}
					if ( array_key_exists( 'defaultsort', $titleprops ) ) {
						$sortkeys[$title->getPrefixedText()] = $titleprops['defaultsort'];
					} else {
						$sortkeys[$title->getPrefixedText()] = $title->getPrefixedText();
					}
				}
			}
		}

		if ( !is_null( $substring ) ) {
			$filtered_pages = array();
			$filtered_sortkeys = array();
			foreach ( $pages as $index => $pageName ) {
				// Filter on the substring manually. It would
				// be better to do this filtering in the
				// original SMW query, but that doesn't seem
				// possible yet.
				// @TODO - this will miss a lot of results for
				// concepts with > 1000 pages. Instead, this
				// code should loop through all the pages,
				// using "offset".
				$lowercasePageName = strtolower( $pageName );
				$position = strpos( $lowercasePageName, $substring );
				if ( $position !== false ) {
					if ( $wgPageFormsAutocompleteOnAllChars ) {
						if ( $position >= 0 ) {
							$filtered_pages[$index] = $pageName;
							$filtered_sortkeys[$index] = $sortkeys[$index];
						}
					} else {
						if ( $position === 0 ||
							strpos( $lowercasePageName, ' ' . $substring ) > 0 ) {
							$filtered_pages[$index] = $pageName;
							$filtered_sortkeys[$index] = $sortkeys[$index];
						}
					}
				}
			}
			$pages = $filtered_pages;
			$sortkeys = $filtered_sortkeys;
		}
		array_multisort( $sortkeys, $pages );
		return $pages;
	}

	public static function getAllPagesForNamespace( $namespace_name, $substring = null ) {
		global $wgContLang, $wgLanguageCode, $wgPageFormsUseDisplayTitle;

		// Cycle through all the namespace names for this language, and
		// if one matches the namespace specified in the form, get the
		// names of all the pages in that namespace.

		// Switch to blank for the string 'Main'.
		if ( $namespace_name == 'Main' || $namespace_name == 'main' ) {
			$namespace_name = '';
		}
		$matchingNamespaceCode = null;
		$namespaces = $wgContLang->getNamespaces();
		foreach ( $namespaces as $curNSCode => $curNSName ) {
			if ( $curNSName == $namespace_name ) {
				$matchingNamespaceCode = $curNSCode;
			}
		}

		// If that didn't find anything, and we're in a language
		// other than English, check English as well.
		if ( is_null( $matchingNamespaceCode ) && $wgLanguageCode != 'en' ) {
			$englishLang = Language::factory( 'en' );
			$namespaces = $englishLang->getNamespaces();
			foreach ( $namespaces as $curNSCode => $curNSName ) {
				if ( $curNSName == $namespace_name ) {
					$matchingNamespaceCode = $curNSCode;
				}
			}
		}

		if ( is_null( $matchingNamespaceCode ) ) {
			return wfMessage( 'pf-missingnamespace', wfEscapeWikiText( $namespace_name ) );
		}

		$db = wfGetDB( DB_SLAVE );
		$tables = array( 'page' );
		$columns = array( 'page_title' );
		$conditions = array();
		$conditions['page_namespace'] = $matchingNamespaceCode;
		if ( $wgPageFormsUseDisplayTitle ) {
			$tables['pp_displaytitle'] = 'page_props';
			$tables['pp_defaultsort'] = 'page_props';
			$columns['pp_displaytitle_value'] = 'pp_displaytitle.pp_value';
			$columns['pp_defaultsort_value'] = 'pp_defaultsort.pp_value';
			$join = array(
				'pp_displaytitle' => array(
					'LEFT JOIN', array(
						'pp_displaytitle.pp_page = page_id',
						'pp_displaytitle.pp_propname = "displaytitle"'
					)
				),
				'pp_defaultsort' => array(
					'LEFT JOIN', array(
						'pp_defaultsort.pp_page = page_id',
						'pp_defaultsort.pp_propname = "defaultsort"'
					)
				)
			);
			if ( $substring != null ) {
				$conditions[] = '(pp_displaytitle.pp_value IS NULL AND (' .
					self::getSQLConditionForAutocompleteInColumn( 'page_title', $substring ) .
					')) OR ' .
					self::getSQLConditionForAutocompleteInColumn( 'pp_displaytitle.pp_value', $substring ) .
					' OR page_namespace = ' . NS_CATEGORY;
			}
		} else {
			$join = array();
			if ( $substring != null ) {
				$conditions[] = self::getSQLConditionForAutocompleteInColumn( 'page_title', $substring );
			}
		}
		$res = $db->select(
			$tables,
			$columns,
			$conditions,
			__METHOD__,
			$options = array(),
			$join );

		$pages = array();
		$sortkeys = array();
		while ( $row = $db->fetchRow( $res ) ) {
			$title = str_replace( '_', ' ', $row[0] );
			if ( array_key_exists( 'pp_displaytitle_value' , $row ) &&
				!is_null( $row[ 'pp_displaytitle_value' ] ) &&
				trim( str_replace( '&#160;', '', strip_tags( $row[ 'pp_displaytitle_value' ] ) ) ) !== '' ) {
				$pages[ $title ] = htmlspecialchars_decode( $row[ 'pp_displaytitle_value'] );
			} else {
				$pages[ $title ] = $title;
			}
			if ( array_key_exists( 'pp_defaultsort_value' , $row ) &&
				!is_null( $row[ 'pp_defaultsort_value' ] ) ) {
				$sortkeys[ $title ] = $row[ 'pp_defaultsort_value'];
			} else {
				$sortkeys[ $title ] = $title;
			}
		}
		$db->freeResult( $res );

		array_multisort( $sortkeys, $pages );
		return $pages;
	}

	/**
	 * Creates an array of values that match the specified source name and
	 * type, for use by both Javascript autocompletion and comboboxes.
	 */
	public static function getAutocompleteValues( $source_name, $source_type ) {
		if ( $source_name == null ) {
			return null;
		}

		// The query depends on whether this is a property, category,
		// concept or namespace.
		if ( $source_type == 'cargo field' ) {
			list( $table_name, $field_name ) = explode( '|', $source_name, 2 );
			$names_array = self::getAllValuesForCargoField( $table_name, $field_name );
			// Remove blank/null values from the array.
			$names_array = array_values( array_filter( $names_array ) );
		} elseif ( $source_type == 'property' ) {
			$names_array = self::getAllValuesForProperty( $source_name );
		} elseif ( $source_type == 'category' ) {
			$names_array = self::getAllPagesForCategory( $source_name, 10 );
		} elseif ( $source_type == 'concept' ) {
			$names_array = self::getAllPagesForConcept( $source_name );
		} else { // i.e., $source_type == 'namespace'
			$names_array = self::getAllPagesForNamespace( $source_name );
		}
		return $names_array;
	}

	/**
	 * Helper function to get an array of values out of what may be either
	 * an array or a delimited string
	 */
	public static function getValuesArray( $value, $delimiter ) {
		if ( is_array( $value ) ) {
			return $value;
		} else {
			// remove extra spaces
			return array_map( 'trim', explode( $delimiter, $value ) );
		}
	}

	public static function getValuesFromExternalURL( $external_url_alias, $substring ) {
		global $wgPageFormsAutocompletionURLs;
		if ( empty( $wgPageFormsAutocompletionURLs ) ) {
			return wfMessage( 'pf-nocompletionurls' );
		}
		if ( ! array_key_exists( $external_url_alias, $wgPageFormsAutocompletionURLs ) ) {
			return wfMessage( 'pf-invalidexturl' );
		}
		$url = $wgPageFormsAutocompletionURLs[$external_url_alias];
		if ( empty( $url ) ) {
			return wfMessage( 'pf-blankexturl' );
		}
		$url = str_replace( '<substr>', urlencode( $substring ), $url );
		$page_contents = Http::get( $url );
		if ( empty( $page_contents ) ) {
			return wfMessage( 'pf-externalpageempty' );
		}
		$data = json_decode( $page_contents );
		if ( empty( $data ) ) {
			return wfMessage( 'pf-externalpagebadjson' );
		}
		$return_values = array();
		foreach ( $data->pfautocomplete as $val ) {
			$return_values[] = (array)$val;
		}
		return $return_values;
	}

	/**
	 * Returns a SQL condition for autocompletion substring value in a column.
	 *
	 * @param string $value_column Value column name
	 * @param string $substring Substring to look for
	 * @return SQL condition for use in WHERE clause
	 */
	public static function getSQLConditionForAutocompleteInColumn( $column, $substring, $replaceSpaces = true ) {
		global $wgDBtype, $wgPageFormsAutocompleteOnAllChars;

		$db = wfGetDB( DB_SLAVE );

		// CONVERT() is also supported in PostgreSQL, but it doesn't
		// seem to work the same way.
		if ( $wgDBtype == 'mysql' ) {
			$column_value = "LOWER(CONVERT($column USING utf8))";
		} else {
			$column_value = "LOWER($column)";
		}

		$substring = strtolower( $substring );
		if ( $replaceSpaces ) {
			$substring = str_replace( ' ', '_', $substring );
		}

		if ( $wgPageFormsAutocompleteOnAllChars ) {
			return $column_value . $db->buildLike( $substring, $db->anyString() );
		} else {
			$spaceRepresentation = $replaceSpaces ? '_' : ' ';
			return $column_value . $db->buildLike( $substring, $db->anyString() ) .
				' OR ' .$column_value .
				$db->buildLike( $db->anyString(), $spaceRepresentation . $substring, $db->anyString() );
		}
	}

	/**
	 * returns an array of pages that are result of the semantic query
	 * @param $rawQueryString string - the query string like [[Category:Trees]][[age::>1000]]
	 * @return array of SMWDIWikiPage objects representing the result
	 */
	public static function getAllPagesForQuery( $rawQuery ) {
		$rawQueryArray = array( $rawQuery );
		SMWQueryProcessor::processFunctionParams( $rawQueryArray, $queryString, $processedParams, $printouts );
		SMWQueryProcessor::addThisPrintout( $printouts, $processedParams );
		$processedParams = SMWQueryProcessor::getProcessedParams( $processedParams, $printouts );
		$queryObj = SMWQueryProcessor::createQuery( $queryString,
			$processedParams,
			SMWQueryProcessor::SPECIAL_PAGE, '', $printouts );
		$res = PFUtils::getSMWStore()->getQueryResult( $queryObj );
		$pages = $res->getResults();

		return $pages;
	}

	public static function disambiguateLabels( $labels ) {
		asort( $labels );
		if ( count( $labels ) == count( array_unique( $labels ) ) ) {
			return $labels;
		}
		$fixed_labels = array();
		foreach ( $labels as $value => $label ) {
			$fixed_labels[$value] = $labels[$value];
		}
		$counts = array_count_values( $fixed_labels );
		foreach ( $counts as $current_label => $count ) {
			if ( $count > 1 ) {
				$matching_keys = array_keys( $labels, $current_label );
				foreach ( $matching_keys as $key ) {
					$fixed_labels[$key] .= ' (' . $key . ')';
				}
			}
		}
		if ( count( $fixed_labels ) == count( array_unique( $fixed_labels ) ) ) {
			return $fixed_labels;
		}
		foreach ( $labels as $value => $label ) {
			$labels[$value] .= ' (' . $value . ')';
		}
		return $labels;
	}

}
