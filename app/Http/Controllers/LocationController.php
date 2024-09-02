<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LocationController extends Controller
{


    public function fhir_locations()
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://chanjoke.intellisoftkenya.com/hapi/fhir/Location?_count=10000',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);
        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            echo 'cURL error: ' . curl_error($curl);
        } else {
            // Decode the JSON response
            $data = json_decode($response, true); // Use 'true' to get an associative array
            $locations = [];
            // Check if 'entry' exists in the response
            if (isset($data['entry']) && is_array($data['entry'])) {
                // Loop through each entry
               
                foreach ($data['entry'] as $entry) {
                    // Extract 'resource' data
                    $resource = $entry['resource'];
        
                    // Access specific fields in each resource
                    $locationId = $resource['id'] ?? 'No ID';
                    $locationName = $resource['name'] ?? 'No Name';
                    $locationType = $resource['type'][0]['coding'][0]['display'] ?? 'No Type';
                    $locationPartOf = $resource['partOf']['reference'] ?? 'Location/Not Part Of Any';
                    $locationPartOf = str_replace('Location/', '', $locationPartOf);
                    $locationName = ucwords(strtolower($locationName)); // Convert to sentence case


                    $locations[] = [
                        'id' => $locationId,
                        'name' => $locationName,
                        'type'=>$locationType,
                        'parent' => $locationPartOf,
                    ];
                }
            } else {
                echo "No entries found in the response.";
            }
        }
        curl_close($curl);
        $jsonLocations = json_encode($locations, JSON_PRETTY_PRINT);

        // Save the JSON data to a file
        $filename = 'locations.json';
        if (file_put_contents($filename, $jsonLocations)) {
            echo "Locations have been saved to '$filename'.\n";
        } else {
            echo "Failed to save locations to '$filename'.\n";
        }

        // narrow down to the entry

    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $csvFilePath = public_path("csv/wards.csv");


        // Open the CSV file for reading
        $handle = fopen($csvFilePath, 'r');


        // Check if the file is successfully opened
        if ($handle !== false) {
            // Read the header to get column names
            $header = fgetcsv($handle);

            // Initialize an array to store data
            $data = [];

            // Read the remaining rows
            while (($row = fgetcsv($handle)) !== false) {
                // Combine header with the current row to create an associative array
                $rowData = array_combine($header, $row);
                // Extract SubCounty name
                $subCounty = $rowData['SubCounty'];

                // Check if the SubCounty key exists in the data array
                if (!isset($data[$subCounty])) {
                    // If not, create an array for the SubCounty
                    $data[$subCounty] = [];
                }

                // Add the current row's data to the SubCounty array
                $data[$subCounty][] = $rowData;
            }

            $results = [];

            foreach ($data as $key => $value) {
                $county = "";
                $subCounty = "";
                $answerOption = [];

                foreach ($value as $item) {
                    $county = strtolower($item['County']);
                    $subCounty = strtolower(str_replace(' ', '-', $item['SubCounty']));
                    $subCountyUpper = strtoupper(str_replace(' ', '-', $item['SubCounty']));
                    $subCountyUpperUnreplaced = strtoupper($item['SubCounty']);
                    $dataHere = [
                        "valueCoding" => [
                            "code" => strtoupper($item['Ward']),
                            "display" => strtoupper($item['Ward']),
                            "system" => "https://locationlist.com/areas"
                        ]
                    ];
                    $answerOption[] =  $dataHere;
                }
                $results[] = [
                    "extension" => [
                        [
                            "url" => "http://hl7.org/fhir/uv/sdc/StructureDefinition/sdc-questionnaire-initialExpression",
                            "valueExpression" => [
                                "language" => "text/fhirpath",
                                "expression" => "Patient.address.state",
                                "name" => "patientDistrict",
                            ],
                        ],
                        [
                            "url" => "http://hl7.org/fhir/StructureDefinition/questionnaire-itemControl",
                            "valueCodeableConcept" => [
                                "coding" => [
                                    [
                                        "system" => "http://hl7.org/fhir/questionnaire-item-control",
                                        "code" => "drop-down",
                                        "display" => "Drop down",
                                    ],
                                ],
                                "text" => "Drop down",
                            ],
                        ],
                    ],
                    "enableWhen" => [
                        [
                            "answerCoding" => [
                                "code" => $subCountyUpper,
                                "display" => $subCountyUpperUnreplaced,
                                "system" => "https://locationlist.com/counties",
                            ],
                            "question" => "PR-address-sub-county-{$county}",
                            "operator" => "=",
                        ],
                    ],
                    "linkId" => "PR-address-ward-{$subCounty}",
                    "type" => "choice",
                    "required" => true,
                    "text" => "Ward *",
                    "item" => [
                        [
                            "linkId" => "sub-county",
                            "text" => "Ward *",
                            "type" => "display",
                            "extension" => [
                                [
                                    "url" => "http://hl7.org/fhir/StructureDefinition/questionnaire-itemControl",
                                    "valueCodeableConcept" => [
                                        "coding" => [
                                            [
                                                "system" => "http://hl7.org/fhir/questionnaire-item-control",
                                                "code" => "flyover",
                                                "display" => "Fly-over",
                                            ],
                                        ],
                                        "text" => "Flyover",
                                    ],
                                ],
                            ],
                        ],
                    ],
                    "answerOption" => $answerOption
                ];
            }


            return $results;
        } else {
            echo "Error opening the CSV file.";
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    public function ward_names()
    {
        $csvFilePath = public_path("csv/wards.csv");
        $handle = fopen($csvFilePath, 'r');
        if ($handle !== false) {
            $header = fgetcsv($handle);
            $data = [];
            while (($row = fgetcsv($handle)) !== false) {
                $rowData = array_combine($header, $row);
                $subCounty = $rowData['SubCounty'];
                if (!isset($data[$subCounty])) {
                    $data[$subCounty] = [];
                }
                $data[$subCounty][] = $rowData;
            }

            $stringNames = [];

            foreach ($data as $key => $value) {

                $subCounty = "";

                foreach ($value as $item) {
                    $county = strtolower($item['County']);
                    $subCounty = strtolower(str_replace(' ', '-', $item['SubCounty']));
                    $stringName = "PR-address-ward-{$subCounty}";
                    $stringNames[] = $stringName;
                }
            }
            $stringNames = array_unique($stringNames);
            $stringNames = array_values($stringNames);

            return $stringNames;
        } else {
            echo "Error opening the CSV file.";
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
