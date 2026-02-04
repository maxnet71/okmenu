<?php

class MenuAI extends Model
{
    protected $table = 'menu_uploads';

    public function createUpload(array $data)
    {
        $sql = "INSERT INTO {$this->table} 
                (locale_id, user_id, filename, filepath, file_type, status) 
                VALUES (:locale_id, :user_id, :filename, :filepath, :file_type, :status)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        
        return $this->db->lastInsertId();
    }

    public function updateStatus(int $id, string $status, array $extraData = []): bool
    {
        $fields = ['status' => $status];
        
        if ($status === 'completed' || $status === 'failed') {
            $fields['processed_at'] = date('Y-m-d H:i:s');
        }
        
        $fields = array_merge($fields, $extraData);
        
        $setClauses = [];
        foreach (array_keys($fields) as $key) {
            $setClauses[] = "`{$key}` = :{$key}";
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClauses) . " WHERE id = :id";
        $fields['id'] = $id;
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($fields);
    }

    public function getByLocaleId(int $localeId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE locale_id = :locale_id ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['locale_id' => $localeId]);
        return $stmt->fetchAll();
    }

    public function extractTextFromImage(string $filepath)
    {
        $imageData = base64_encode(file_get_contents($filepath));
        $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        
        $mediaTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp'
        ];
        
        $mediaType = $mediaTypes[$extension] ?? 'image/jpeg';
        
        try {
            return $this->callAnthropicAPI($imageData, $mediaType, 'image', 'claude-haiku-4-20250514');
        } catch (Exception $e) {
            error_log("Haiku fallito per immagine, uso Sonnet: " . $e->getMessage());
            return $this->callAnthropicAPI($imageData, $mediaType, 'image', 'claude-sonnet-4-20250514');
        }
    }

    public function extractTextFromPDF(string $filepath)
    {
        $pdfData = base64_encode(file_get_contents($filepath));
        
        try {
            return $this->callAnthropicAPI($pdfData, 'application/pdf', 'document', 'claude-haiku-4-20250514');
        } catch (Exception $e) {
            error_log("Haiku fallito per PDF, uso Sonnet: " . $e->getMessage());
            return $this->callAnthropicAPI($pdfData, 'application/pdf', 'document', 'claude-sonnet-4-20250514');
        }
    }

    private function callAnthropicAPI(string $base64Data, string $mediaType, string $sourceType, string $model = 'claude-haiku-4-20250514')
    {
        $apiKey = defined('ANTHROPIC_API_KEY') ? ANTHROPIC_API_KEY : '';
        
        if (empty($apiKey)) {
            throw new Exception('ANTHROPIC_API_KEY non configurata');
        }

        $prompt = "Analizza questo menu e estrai tutte le informazioni in formato JSON strutturato.

Rispondi SOLO con JSON valido, senza testo aggiuntivo.

Formato richiesto:
{
  \"menu\": {
    \"nome\": \"Nome del menu o ristorante se presente\",
    \"descrizione\": \"Breve descrizione se presente\"
  },
  \"categorie\": [
    {
      \"nome\": \"Nome categoria\",
      \"descrizione\": \"Descrizione categoria se presente\",
      \"piatti\": [
        {
          \"nome\": \"Nome piatto\",
          \"descrizione\": \"Descrizione piatto\",
          \"ingredienti\": \"Elenco ingredienti se presente\",
          \"prezzo\": 0.00,
          \"allergeni\": [\"A1\", \"A7\"],
          \"caratteristiche\": [\"Vegano\", \"Bio\"]
        }
      ]
    }
  ]
}

Codici allergeni: A1=Glutine, A2=Crostacei, A3=Uova, A4=Pesce, A5=Arachidi, A6=Soia, A7=Latte, A8=Frutta a guscio, A9=Sedano, A10=Senape, A11=Sesamo, A12=Solfiti, A13=Lupini, A14=Molluschi

Caratteristiche: Vegano, Vegetariano, Senza Glutine, Bio, Piccante, Novità

Se il prezzo non è indicato, metti 0.00";

        $payload = [
            'model' => $model,
            'max_tokens' => 4096,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => $sourceType,
                            'source' => [
                                'type' => 'base64',
                                'media_type' => $mediaType,
                                'data' => $base64Data
                            ]
                        ],
                        [
                            'type' => 'text',
                            'text' => $prompt
                        ]
                    ]
                ]
            ]
        ];

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("Errore API Anthropic (modello: {$model}): HTTP {$httpCode}");
        }

        $result = json_decode($response, true);
        
        if (!isset($result['content'][0]['text'])) {
            throw new Exception('Risposta API non valida');
        }

        return $result['content'][0]['text'];
    }

    public function parseMenuJSON(string $jsonResponse)
    {
        $jsonResponse = preg_replace('/^```json\s*/', '', $jsonResponse);
        $jsonResponse = preg_replace('/\s*```$/', '', $jsonResponse);
        $jsonResponse = trim($jsonResponse);
        
        $data = json_decode($jsonResponse, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
        
        return $data;
    }

    public function insertMenuData(int $localeId, array $menuData): int
    {
        $itemsCreated = 0;

        try {
            $this->db->beginTransaction();

            $menuModel = new Menu();
            $categoriaModel = new Categoria();
            $piattoModel = new Piatto();

            $menuNome = $menuData['menu']['nome'] ?? 'Menu da AI';
            $menuDescrizione = $menuData['menu']['descrizione'] ?? null;

            $menuId = $menuModel->insert([
                'locale_id' => $localeId,
                'nome' => $menuNome,
                'descrizione' => $menuDescrizione,
                'tipo' => 'principale',
                'pubblicato' => 0,
                'ordinamento' => 0
            ]);

            $allergeniMap = $this->getAllergeniMap();
            $caratteristicheMap = $this->getCaratteristicheMap();

            foreach ($menuData['categorie'] ?? [] as $idx => $catData) {
                $categoriaId = $categoriaModel->insert([
                    'menu_id' => $menuId,
                    'nome' => $catData['nome'],
                    'descrizione' => $catData['descrizione'] ?? null,
                    'ordinamento' => $idx,
                    'attivo' => 1
                ]);

                $itemsCreated++;

                foreach ($catData['piatti'] ?? [] as $piattoIdx => $piattoData) {
                    $piattoId = $piattoModel->insert([
                        'categoria_id' => $categoriaId,
                        'nome' => $piattoData['nome'],
                        'descrizione' => $piattoData['descrizione'] ?? null,
                        'ingredienti' => $piattoData['ingredienti'] ?? null,
                        'prezzo' => $piattoData['prezzo'] ?? 0.00,
                        'mostra_prezzo' => ($piattoData['prezzo'] ?? 0) > 0 ? 1 : 0,
                        'disponibile' => 1,
                        'ordinamento' => $piattoIdx
                    ]);

                    $itemsCreated++;

                    if (!empty($piattoData['allergeni'])) {
                        foreach ($piattoData['allergeni'] as $allergeneCode) {
                            if (isset($allergeniMap[$allergeneCode])) {
                                $stmt = $this->db->prepare(
                                    "INSERT INTO piatti_allergeni (piatto_id, allergene_id) VALUES (:piatto_id, :allergene_id)"
                                );
                                $stmt->execute([
                                    'piatto_id' => $piattoId,
                                    'allergene_id' => $allergeniMap[$allergeneCode]
                                ]);
                            }
                        }
                    }

                    if (!empty($piattoData['caratteristiche'])) {
                        foreach ($piattoData['caratteristiche'] as $caratteristicaNome) {
                            if (isset($caratteristicheMap[$caratteristicaNome])) {
                                $stmt = $this->db->prepare(
                                    "INSERT INTO piatti_caratteristiche (piatto_id, caratteristica_id) VALUES (:piatto_id, :caratteristica_id)"
                                );
                                $stmt->execute([
                                    'piatto_id' => $piattoId,
                                    'caratteristica_id' => $caratteristicheMap[$caratteristicaNome]
                                ]);
                            }
                        }
                    }
                }
            }

            $this->db->commit();
            return $itemsCreated;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function getAllergeniMap(): array
    {
        $sql = "SELECT id, codice FROM allergeni";
        $stmt = $this->db->query($sql);
        $map = [];
        while ($row = $stmt->fetch()) {
            $map[$row['codice']] = $row['id'];
        }
        return $map;
    }

    private function getCaratteristicheMap(): array
    {
        $sql = "SELECT id, nome FROM caratteristiche";
        $stmt = $this->db->query($sql);
        $map = [];
        while ($row = $stmt->fetch()) {
            $map[$row['nome']] = $row['id'];
        }
        return $map;
    }
}