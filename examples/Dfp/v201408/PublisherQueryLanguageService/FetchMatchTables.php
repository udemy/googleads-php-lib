<?php
/**
 * This example fetches and creates match table files from the Line_Item and
 * Ad_Unit tables. This example may take a while to run.
 *
 * NOTE: Since this example loads all results into memory, your PHP memory_limit
 *       may need to be raised for this example to work properly.
 *
 * A full list of available tables can be found at:
 * https://developers.google.com/doubleclick-publishers/docs/reference/v201408/PublisherQueryLanguageService
 *
 * Tags: PublisherQueryLanguageService.select
 *
 * PHP version 5
 *
 * Copyright 2013, Google Inc. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @package    GoogleApiAdsDfp
 * @subpackage v201408
 * @category   WebServices
 * @copyright  2013, Google Inc. All Rights Reserved.
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache License,
 *             Version 2.0
 * @author     Vincent Tsao
 */
error_reporting(E_STRICT | E_ALL);

// You can set the include path to src directory or reference
// DfpUser.php directly via require_once.
// $path = '/path/to/dfp_api_php_lib/src';
$path = dirname(__FILE__) . '/../../../../src';
set_include_path(get_include_path() . PATH_SEPARATOR . $path);

require_once 'Google/Api/Ads/Dfp/Lib/DfpUser.php';
require_once 'Google/Api/Ads/Dfp/Util/Pql.php';
require_once dirname(__FILE__) . '/../../../Common/ExampleUtils.php';

try {
  // Get DfpUser from credentials in "../auth.ini"
  // relative to the DfpUser.php file's directory.
  $user = new DfpUser();

  // Log SOAP XML request and response.
  $user->LogDefaults();

  // Get the PublisherQueryLanguageService.
  $pqlService = $user->GetService('PublisherQueryLanguageService', 'v201408');

  // Statement parts to help build a statement to select all line items.
  $lineItemPqlTemplate = 'SELECT Id, Name, Status FROM Line_Item ORDER BY Id '
      . 'ASC LIMIT %d OFFSET %d';
  // Statement parts to help build a statement to select all ad units.
  $adUnitPqlTemplate = 'SELECT Id, Name FROM Ad_Unit ORDER BY Id ASC LIMIT '
      . '%d OFFSET %d';

  $lineItemFilePath = fetchMatchTable($lineItemPqlTemplate, $pqlService,
      "Line-Item-Matchtable");
  $adUnitFilePath = fetchMatchTable($adUnitPqlTemplate, $pqlService,
      "Ad-Unit-Matchtable");

  printf("Line items saved to %s\n", $lineItemFilePath);
  printf("Ad units saved to %s\n", $adUnitFilePath);
} catch (OAuth2Exception $e) {
  ExampleUtils::CheckForOAuth2Errors($e);
} catch (ValidationException $e) {
  ExampleUtils::CheckForOAuth2Errors($e);
} catch (Exception $e) {
  printf("%s\n", $e->getMessage());
}

/**
 * Fetches a match table from a PQL statement and writes it to a file.
 */
function fetchMatchTable($pqlTemplate, $pqlService, $fileName) {
  $SUGGESTED_PAGE_LIMIT = 500;
  $offset = 0;
  $i = 0;

  do {
    $resultSet = $pqlService->select(new Statement(sprintf($pqlTemplate,
        $SUGGESTED_PAGE_LIMIT, $offset)));

    // Combine result sets with previous ones.
    $combinedResultSet = (!isset($combinedResultSet))
        ? $resultSet
        : Pql::CombineResultSets($combinedResultSet, $resultSet);

    $offset += $SUGGESTED_PAGE_LIMIT;
  } while (isset($resultSet->rows) && count($resultSet->rows) > 0);

  // Change to your file location.
  $filePath = sprintf("%s/%s-%s.csv", sys_get_temp_dir(), $fileName, uniqid());
  $fp = fopen($filePath, 'w');

  // Write the result set to a CSV.
  fputcsv($fp, Pql::GetColumnLabels($combinedResultSet));
  foreach ($combinedResultSet->rows as $row) {
     fputcsv($fp, Pql::GetRowStringValues($row));
  }
  fclose($fp);

  return $filePath;
}

