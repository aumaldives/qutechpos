<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class ApiDocumentationController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the interactive API documentation
     *
     * @return \Illuminate\Http\Response
     */
    public function interactive()
    {
        if (!auth()->user()->can('view_api_docs')) {
            abort(403, 'Unauthorized action.');
        }
        
        // Pass the OpenAPI spec URL to the view
        $openApiSpecUrl = url('/api/openapi.yaml');
        
        return view('api_docs.interactive', compact('openApiSpecUrl'));
    }

    /**
     * Display the API playground for testing
     *
     * @return \Illuminate\Http\Response
     */
    public function playground()
    {
        if (!auth()->user()->can('view_api_docs')) {
            abort(403, 'Unauthorized action.');
        }
        
        $user = auth()->user();
        $business_id = session('user.business_id');
        
        // Get user's API keys for testing
        $apiKeys = \App\ApiKey::where('business_id', $business_id)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('api_docs.playground', compact('apiKeys'));
    }

    /**
     * Get OpenAPI specification as JSON
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function openApiJson()
    {
        if (!auth()->user()->can('view_api_docs')) {
            abort(403, 'Unauthorized action.');
        }
        
        $yamlPath = public_path('api/openapi.yaml');
        
        if (!file_exists($yamlPath)) {
            return response()->json(['error' => 'OpenAPI specification not found'], 404);
        }
        
        try {
            // Convert YAML to array and then to JSON
            $yamlContent = file_get_contents($yamlPath);
            $data = yaml_parse($yamlContent);
            
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to parse OpenAPI specification'], 500);
        }
    }

    /**
     * Generate code examples for different programming languages
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function codeExamples(Request $request)
    {
        if (!auth()->user()->can('view_api_docs')) {
            abort(403, 'Unauthorized action.');
        }
        
        $endpoint = $request->get('endpoint', '/products');
        $method = $request->get('method', 'GET');
        
        $examples = $this->generateCodeExamples($endpoint, $method);
        
        return view('api_docs.code_examples', compact('examples', 'endpoint', 'method'));
    }

    /**
     * Generate code examples for different programming languages
     *
     * @param string $endpoint
     * @param string $method
     * @return array
     */
    private function generateCodeExamples($endpoint, $method)
    {
        $baseUrl = url('/api/v1');
        $fullUrl = $baseUrl . $endpoint;
        
        return [
            'curl' => $this->generateCurlExample($fullUrl, $method),
            'javascript' => $this->generateJavaScriptExample($fullUrl, $method),
            'php' => $this->generatePhpExample($fullUrl, $method),
            'python' => $this->generatePythonExample($fullUrl, $method),
        ];
    }

    private function generateCurlExample($url, $method)
    {
        $example = "curl -X {$method} \\\n";
        $example .= "  '{$url}' \\\n";
        $example .= "  -H 'Accept: application/json' \\\n";
        $example .= "  -H 'X-API-Key: YOUR_API_KEY'";
        
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $example .= " \\\n  -H 'Content-Type: application/json' \\\n";
            
            // Generate specific examples for POS endpoints
            if (strpos($url, '/pos/sale') !== false) {
                $example .= "  -d '" . json_encode($this->getPOSSaleData(), JSON_PRETTY_PRINT) . "'";
            } elseif (strpos($url, '/pos/drafts') !== false) {
                $example .= "  -d '" . json_encode($this->getPOSDraftData(), JSON_PRETTY_PRINT) . "'";
            } else {
                $example .= "  -d '{\"key\": \"value\"}'";
            }
        }
        
        return $example;
    }

    private function generateJavaScriptExample($url, $method)
    {
        $example = "const response = await fetch('{$url}', {\n";
        $example .= "  method: '{$method}',\n";
        $example .= "  headers: {\n";
        $example .= "    'Accept': 'application/json',\n";
        $example .= "    'X-API-Key': 'YOUR_API_KEY'";
        
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $example .= ",\n    'Content-Type': 'application/json'\n";
            $example .= "  },\n";
            
            // Generate specific examples for POS endpoints
            if (strpos($url, '/pos/sale') !== false) {
                $posData = $this->getPOSSaleData();
                $example .= "  body: JSON.stringify(" . json_encode($posData, JSON_PRETTY_PRINT) . ")";
            } elseif (strpos($url, '/pos/drafts') !== false) {
                $draftData = $this->getPOSDraftData();
                $example .= "  body: JSON.stringify(" . json_encode($draftData, JSON_PRETTY_PRINT) . ")";
            } else {
                $example .= "  body: JSON.stringify({\n    key: 'value'\n  })";
            }
        } else {
            $example .= "\n  }";
        }
        
        $example .= "\n});\n\n";
        $example .= "const data = await response.json();\n";
        $example .= "console.log(data);";
        
        return $example;
    }

    private function generatePhpExample($url, $method)
    {
        $example = "<?php\n\n";
        $example .= "\$curl = curl_init();\n\n";
        $example .= "curl_setopt_array(\$curl, [\n";
        $example .= "    CURLOPT_URL => '{$url}',\n";
        $example .= "    CURLOPT_RETURNTRANSFER => true,\n";
        $example .= "    CURLOPT_CUSTOMREQUEST => '{$method}',\n";
        $example .= "    CURLOPT_HTTPHEADER => [\n";
        $example .= "        'Accept: application/json',\n";
        $example .= "        'X-API-Key: YOUR_API_KEY'";
        
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $example .= ",\n        'Content-Type: application/json'\n";
            $example .= "    ],\n";
            
            // Generate specific examples for POS endpoints
            if (strpos($url, '/pos/sale') !== false) {
                $posData = $this->getPOSSaleData();
                $example .= "    CURLOPT_POSTFIELDS => json_encode(" . var_export($posData, true) . ")";
            } elseif (strpos($url, '/pos/drafts') !== false) {
                $draftData = $this->getPOSDraftData();
                $example .= "    CURLOPT_POSTFIELDS => json_encode(" . var_export($draftData, true) . ")";
            } else {
                $example .= "    CURLOPT_POSTFIELDS => json_encode([\n";
                $example .= "        'key' => 'value'\n";
                $example .= "    ])";
            }
        } else {
            $example .= "\n    ]";
        }
        
        $example .= "\n]);\n\n";
        $example .= "\$response = curl_exec(\$curl);\n";
        $example .= "\$data = json_decode(\$response, true);\n\n";
        $example .= "curl_close(\$curl);\n\n";
        $example .= "print_r(\$data);";
        
        return $example;
    }

    private function generatePythonExample($url, $method)
    {
        $example = "import requests\n";
        $example .= "import json\n\n";
        $example .= "headers = {\n";
        $example .= "    'Accept': 'application/json',\n";
        $example .= "    'X-API-Key': 'YOUR_API_KEY'";
        
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $example .= ",\n    'Content-Type': 'application/json'\n";
            $example .= "}\n\n";
            
            // Generate specific examples for POS endpoints
            if (strpos($url, '/pos/sale') !== false) {
                $posData = $this->getPOSSaleData();
                $example .= "data = " . $this->arrayToPythonDict($posData) . "\n\n";
            } elseif (strpos($url, '/pos/drafts') !== false) {
                $draftData = $this->getPOSDraftData();
                $example .= "data = " . $this->arrayToPythonDict($draftData) . "\n\n";
            } else {
                $example .= "data = {\n";
                $example .= "    'key': 'value'\n";
                $example .= "}\n\n";
            }
            
            $example .= "response = requests.{$method}(\n";
            $example .= "    '{$url}',\n";
            $example .= "    headers=headers,\n";
            $example .= "    json=data\n";
            $example .= ")";
        } else {
            $example .= "\n}\n\n";
            $example .= "response = requests.{$method}(\n";
            $example .= "    '{$url}',\n";
            $example .= "    headers=headers\n";
            $example .= ")";
        }
        
        $example .= "\n\n";
        $example .= "if response.status_code == 200:\n";
        $example .= "    data = response.json()\n";
        $example .= "    print(json.dumps(data, indent=2))\n";
        $example .= "else:\n";
        $example .= "    print(f'Error: {response.status_code} - {response.text}')";
        
        return $example;
    }

    /**
     * Get sample data for POS sale endpoint
     *
     * @return array
     */
    private function getPOSSaleData()
    {
        return [
            'location_id' => 1,
            'contact_id' => 5,
            'products' => [
                [
                    'variation_id' => 10,
                    'quantity' => 2.0,
                    'unit_price' => 25.50
                ],
                [
                    'variation_id' => 15,
                    'quantity' => 1.0,
                    'unit_price' => 45.00
                ]
            ],
            'plastic_bags' => [
                [
                    'type_id' => 1,
                    'quantity' => 2
                ]
            ],
            'payment' => [
                [
                    'method' => 'cash',
                    'amount' => 50.00
                ],
                [
                    'method' => 'card',
                    'amount' => 46.50
                ]
            ],
            'discount_amount' => 5.00,
            'discount_type' => 'fixed',
            'tax_id' => 1,
            'shipping_charges' => 2.50,
            'is_credit_sale' => false,
            'commission_agent' => 3,
            'sale_note' => 'Customer requested express delivery',
            'staff_note' => 'Regular customer - VIP treatment'
        ];
    }

    /**
     * Get sample data for POS draft endpoint
     *
     * @return array
     */
    private function getPOSDraftData()
    {
        return [
            'location_id' => 1,
            'contact_id' => 8,
            'products' => [
                [
                    'variation_id' => 12,
                    'quantity' => 3.0,
                    'unit_price' => 18.75
                ]
            ]
        ];
    }

    /**
     * Convert PHP array to Python dictionary format for code examples
     *
     * @param array $array
     * @return string
     */
    private function arrayToPythonDict($array)
    {
        $json = json_encode($array, JSON_PRETTY_PRINT);
        // Convert JSON format to Python dict format
        $python = str_replace(['"true"', '"false"', '"null"'], ['True', 'False', 'None'], $json);
        $python = preg_replace('/"([^"]+)":/', '$1:', $python);
        return $python;
    }
}