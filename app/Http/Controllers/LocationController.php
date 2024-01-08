<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $csvFilePath = public_path("csv/other.csv");


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
