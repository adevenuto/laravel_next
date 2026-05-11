# Hyperlocal Content Generator - Build Plan

## Phase Overview
**Total Timeline:** 2-3 weeks for MVP  
**Tech Stack:** Laravel (backend), Vue/React (frontend), Claude API (content generation), PostgreSQL (database)

---

## Week 1: Core Infrastructure & MVP Foundation

### Day 1-2: Project Setup

#### 1.1 Create Laravel Project
```bash
# Create new Laravel project
composer create-project laravel/laravel hyperlocal
cd hyperlocal

# Install required packages
composer require anthropic-ai/sdk # Claude API SDK
composer require laravel/sanctum # API authentication
composer require guzzlehttp/guzzle # HTTP client

# For CSV import
composer require goodby/csv # CSV parsing
```

#### 1.2 Environment Configuration
```bash
# .env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=hyperlocal
DB_USERNAME=postgres

ANTHROPIC_API_KEY=your_key_here
ANTHROPIC_MODEL=claude-opus-4-20250514

# Queue configuration (for background jobs)
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

#### 1.3 Database Setup
```bash
# Create PostgreSQL database
createdb hyperlocal

# Run migrations
php artisan migrate
```

---

### Day 3-4: Database Schema & Models

#### 2.1 Create Migrations

**Create Users Table (already exists, extend it):**
```php
// database/migrations/2024_01_01_000001_create_users_table.php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->string('password');
    $table->string('company_name')->nullable();
    $table->text('brand_guidelines')->nullable(); // JSON: colors, fonts, tone
    $table->timestamps();
});
```

**Brands Table:**
```php
// database/migrations/2024_01_03_000002_create_brands_table.php
Schema::create('brands', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('name');
    $table->text('description')->nullable();
    $table->json('brand_kit'); // {colors: [], fonts: [], logos: []}
    $table->json('content_rules'); // {min_locations_per_post: 2, tone: 'friendly'}
    $table->timestamps();
});
```

**Locations Table:**
```php
// database/migrations/2024_01_03_000003_create_locations_table.php
Schema::create('locations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('brand_id')->constrained()->onDelete('cascade');
    $table->string('name'); // "Downtown Dental - Main St"
    $table->string('address');
    $table->string('city');
    $table->string('state');
    $table->string('zip');
    $table->string('phone');
    $table->string('hours')->nullable(); // "Mon-Fri 9am-5pm"
    $table->json('specialties')->nullable(); // ["teeth_whitening", "implants"]
    $table->json('local_info')->nullable(); // Custom data per location
    $table->boolean('active')->default(true);
    $table->timestamps();
});
```

**Staff Table:**
```php
// database/migrations/2024_01_03_000004_create_staff_table.php
Schema::create('staff', function (Blueprint $table) {
    $table->id();
    $table->foreignId('location_id')->constrained()->onDelete('cascade');
    $table->string('name');
    $table->string('role'); // "Dentist", "Hygienist", "Receptionist"
    $table->text('bio')->nullable();
    $table->string('photo_url')->nullable(); // S3 path
    $table->json('credentials')->nullable(); // ["DDS", "10 years experience"]
    $table->boolean('featured')->default(false); // For spotlights
    $table->timestamps();
});
```

**Templates Table:**
```php
// database/migrations/2024_01_03_000005_create_templates_table.php
Schema::create('templates', function (Blueprint $table) {
    $table->id();
    $table->foreignId('brand_id')->constrained()->onDelete('cascade');
    $table->string('name'); // "Weekly Promotion"
    $table->string('type'); // 'instagram', 'facebook', 'email', 'sms'
    $table->text('template_text'); // Contains {variables}
    $table->json('variables'); // ["location_name", "staff_name", "promotion"]
    $table->json('claude_prompt')->nullable(); // Instructions for Claude
    $table->integer('usage_count')->default(0);
    $table->integer('engagement_score')->default(0);
    $table->timestamps();
});
```

**Generated Content Table:**
```php
// database/migrations/2024_01_03_000006_create_generated_content_table.php
Schema::create('generated_content', function (Blueprint $table) {
    $table->id();
    $table->foreignId('location_id')->constrained()->onDelete('cascade');
    $table->foreignId('template_id')->constrained()->onDelete('cascade');
    $table->string('type'); // 'instagram', 'facebook', 'email', 'sms'
    $table->text('content'); // The generated text
    $table->json('metadata'); // {subject, cta, hashtags}
    $table->enum('status', ['draft', 'approved', 'scheduled', 'published'])->default('draft');
    $table->datetime('scheduled_at')->nullable();
    $table->json('ai_feedback')->nullable(); // Suggestions from Claude
    $table->integer('likes')->default(0); // For analytics
    $table->integer('comments')->default(0);
    $table->integer('shares')->default(0);
    $table->timestamps();
});
```

**Run Migrations:**
```bash
php artisan migrate
```

#### 2.2 Create Eloquent Models

**User Model:**
```php
// app/Models/User.php
class User extends Model {
    protected $fillable = ['name', 'email', 'password', 'company_name'];
    protected $casts = ['brand_guidelines' => 'array'];
    
    public function brands() {
        return $this->hasMany(Brand::class);
    }
}
```

**Brand Model:**
```php
// app/Models/Brand.php
class Brand extends Model {
    protected $fillable = ['name', 'description', 'brand_kit', 'content_rules'];
    protected $casts = ['brand_kit' => 'array', 'content_rules' => 'array'];
    
    public function locations() {
        return $this->hasMany(Location::class);
    }
    
    public function templates() {
        return $this->hasMany(Template::class);
    }
}
```

**Location Model:**
```php
// app/Models/Location.php
class Location extends Model {
    protected $fillable = ['brand_id', 'name', 'address', 'city', 'state', 'zip', 'phone', 'specialties'];
    protected $casts = ['specialties' => 'array', 'local_info' => 'array'];
    
    public function brand() {
        return $this->belongsTo(Brand::class);
    }
    
    public function staff() {
        return $this->hasMany(Staff::class);
    }
    
    public function generatedContent() {
        return $this->hasMany(GeneratedContent::class);
    }
}
```

**Staff Model:**
```php
// app/Models/Staff.php
class Staff extends Model {
    protected $fillable = ['location_id', 'name', 'role', 'bio', 'photo_url', 'credentials'];
    protected $casts = ['credentials' => 'array'];
    
    public function location() {
        return $this->belongsTo(Location::class);
    }
}
```

**Template Model:**
```php
// app/Models/Template.php
class Template extends Model {
    protected $fillable = ['brand_id', 'name', 'type', 'template_text', 'variables', 'claude_prompt'];
    protected $casts = ['variables' => 'array', 'claude_prompt' => 'array'];
    
    public function brand() {
        return $this->belongsTo(Brand::class);
    }
    
    public function generatedContent() {
        return $this->hasMany(GeneratedContent::class);
    }
}
```

**GeneratedContent Model:**
```php
// app/Models/GeneratedContent.php
class GeneratedContent extends Model {
    protected $table = 'generated_content';
    protected $fillable = ['location_id', 'template_id', 'type', 'content', 'metadata', 'status'];
    protected $casts = ['metadata' => 'array'];
    
    public function location() {
        return $this->belongsTo(Location::class);
    }
    
    public function template() {
        return $this->belongsTo(Template::class);
    }
}
```

---

### Day 5-7: Core Services & Claude Integration

#### 3.1 Create Claude Service

```php
// app/Services/ClaudeContentGenerator.php
<?php

namespace App\Services;

use Anthropic\Anthropic;
use App\Models\Location;
use App\Models\Template;
use App\Models\GeneratedContent;

class ClaudeContentGenerator
{
    protected $client;
    protected $model = 'claude-opus-4-20250514';
    
    public function __construct()
    {
        $this->client = new Anthropic([
            'apiKey' => env('ANTHROPIC_API_KEY'),
        ]);
    }
    
    /**
     * Generate content for a location using a template
     */
    public function generateContent(Location $location, Template $template): string
    {
        // 1. Build variable map
        $variables = $this->buildVariables($location, $template);
        
        // 2. Build prompt for Claude
        $systemPrompt = $this->buildSystemPrompt($location, $template);
        $userPrompt = $this->buildUserPrompt($location, $template, $variables);
        
        // 3. Call Claude API
        $response = $this->client->messages->create([
            'model' => $this->model,
            'max_tokens' => 500,
            'system' => $systemPrompt,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $userPrompt,
                ],
            ],
        ]);
        
        // 4. Extract generated content
        $generatedText = $response->content[0]->text;
        
        // 5. Store in database
        GeneratedContent::create([
            'location_id' => $location->id,
            'template_id' => $template->id,
            'type' => $template->type,
            'content' => $generatedText,
            'metadata' => $this->extractMetadata($generatedText, $template->type),
            'status' => 'draft',
        ]);
        
        return $generatedText;
    }
    
    /**
     * Build variables from location + template
     */
    private function buildVariables(Location $location, Template $template): array
    {
        $staff = $location->staff()->first();
        
        return [
            'location_name' => $location->name,
            'location_city' => $location->city,
            'location_address' => $location->address,
            'location_phone' => $location->phone,
            'location_hours' => $location->hours,
            'staff_name' => $staff?->name ?? 'Team',
            'staff_role' => $staff?->role ?? 'Professional',
            'staff_bio' => $staff?->bio ?? '',
            'brand_name' => $location->brand->name,
            'specialties' => implode(', ', $location->specialties ?? []),
        ];
    }
    
    /**
     * Build system prompt based on brand guidelines
     */
    private function buildSystemPrompt(Location $location, Template $template): string
    {
        $brand = $location->brand;
        $rules = $brand->content_rules ?? [];
        
        return "You are a social media content expert creating marketing content for {$brand->name}.

Brand tone: {$rules['tone'] ?? 'professional and friendly'}
Content rules: " . json_encode($rules) . "

Important:
- Make content specific to {$location->city} when possible
- Include local references or events if relevant
- Keep content authentic and helpful
- Include a clear call-to-action
- For Instagram: Keep it concise and engaging
- For Email: Can be longer, conversational tone
- For SMS: Under 160 characters, mobile-first";
    }
    
    /**
     * Build user prompt with template and variables
     */
    private function buildUserPrompt(Location $location, Template $template, array $variables): string
    {
        $variableText = "Available variables to use:\n";
        foreach ($variables as $key => $value) {
            $variableText .= "- {$key}: {$value}\n";
        }
        
        return "Generate a {$template->type} post using this template:\n\n" . 
               $template->template_text . "\n\n" .
               $variableText . "\n\n" .
               "Create content that is unique and relevant to " . $location->city . ".";
    }
    
    /**
     * Extract metadata (hashtags, CTA, etc) from generated content
     */
    private function extractMetadata(string $content, string $type): array
    {
        // Simple regex-based extraction
        $hashtags = [];
        if (preg_match_all('/#\w+/', $content, $matches)) {
            $hashtags = $matches[0];
        }
        
        return [
            'hashtags' => $hashtags,
            'word_count' => str_word_count($content),
            'type' => $type,
        ];
    }
}
```

#### 3.2 Create Content Generation Job

```php
// app/Jobs/GenerateLocationContent.php
<?php

namespace App\Jobs;

use App\Models\Location;
use App\Models\Template;
use App\Services\ClaudeContentGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class GenerateLocationContent implements ShouldQueue
{
    use Queueable, SerializesModels, InteractsWithQueue;
    
    public function __construct(
        protected Location $location,
        protected Template $template
    ) {}
    
    public function handle(ClaudeContentGenerator $generator)
    {
        try {
            $generator->generateContent($this->location, $this->template);
        } catch (\Exception $e) {
            \Log::error('Content generation failed', [
                'location_id' => $this->location->id,
                'template_id' => $this->template->id,
                'error' => $e->getMessage(),
            ]);
            
            $this->fail($e);
        }
    }
}
```

#### 3.3 Create Batch Generation Command

```php
// app/Console/Commands/GenerateWeeklyContent.php
<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Location;
use App\Models\Template;
use App\Jobs\GenerateLocationContent;
use Illuminate\Console\Command;

class GenerateWeeklyContent extends Command
{
    protected $signature = 'content:generate-weekly {--brand-id=}';
    protected $description = 'Generate weekly content for all locations';
    
    public function handle()
    {
        $query = Brand::query();
        
        if ($brandId = $this->option('brand-id')) {
            $query->where('id', $brandId);
        }
        
        $brands = $query->get();
        $jobsDispatched = 0;
        
        foreach ($brands as $brand) {
            // Get active templates (instagram, facebook, email)
            $templates = $brand->templates()
                ->whereIn('type', ['instagram', 'facebook', 'email'])
                ->get();
            
            // Get all active locations
            $locations = $brand->locations()->where('active', true)->get();
            
            // Dispatch job for each location x template combination
            foreach ($locations as $location) {
                foreach ($templates as $template) {
                    GenerateLocationContent::dispatch($location, $template);
                    $jobsDispatched++;
                }
            }
        }
        
        $this->info("Dispatched {$jobsDispatched} content generation jobs");
    }
}
```

---

## Week 2: API Routes & Dashboard UI

### Day 1-2: Create API Routes

#### 4.1 API Controllers

**BrandController:**
```php
// app/Http/Controllers/Api/BrandController.php
<?php

namespace App\Http\Controllers\Api;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class BrandController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'brand_kit' => 'required|array',
            'content_rules' => 'required|array',
        ]);
        
        $brand = auth()->user()->brands()->create($validated);
        
        return response()->json($brand, 201);
    }
    
    public function show(Brand $brand)
    {
        $this->authorize('view', $brand);
        
        return response()->json($brand->load('locations', 'templates'));
    }
    
    public function update(Request $request, Brand $brand)
    {
        $this->authorize('update', $brand);
        
        $brand->update($request->validated());
        
        return response()->json($brand);
    }
}
```

**LocationController:**
```php
// app/Http/Controllers/Api/LocationController.php
<?php

namespace App\Http\Controllers\Api;

use App\Models\Location;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class LocationController extends Controller
{
    public function store(Request $request)
    {
        $brand = auth()->user()->brands()->findOrFail($request->brand_id);
        
        $validated = $request->validate([
            'name' => 'required|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'zip' => 'required|string',
            'phone' => 'required|string',
            'specialties' => 'nullable|array',
        ]);
        
        $location = $brand->locations()->create($validated);
        
        return response()->json($location, 201);
    }
    
    public function bulk(Request $request)
    {
        // Import locations from CSV
        $brand = auth()->user()->brands()->findOrFail($request->brand_id);
        $file = $request->file('csv');
        
        // Parse CSV (use goodby/csv package)
        $locations = [];
        // ... CSV parsing logic
        
        foreach ($locations as $data) {
            $brand->locations()->create($data);
        }
        
        return response()->json(['imported' => count($locations)]);
    }
    
    public function generatedContent(Location $location)
    {
        return response()->json(
            $location->generatedContent()
                ->with('template')
                ->latest()
                ->paginate(20)
        );
    }
}
```

**TemplateController:**
```php
// app/Http/Controllers/Api/TemplateController.php
<?php

namespace App\Http\Controllers\Api;

use App\Models\Template;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TemplateController extends Controller
{
    public function store(Request $request)
    {
        $brand = auth()->user()->brands()->findOrFail($request->brand_id);
        
        $validated = $request->validate([
            'name' => 'required|string',
            'type' => 'required|in:instagram,facebook,email,sms',
            'template_text' => 'required|string',
            'claude_prompt' => 'nullable|array',
        ]);
        
        // Extract variables from template (e.g., {location_name})
        preg_match_all('/\{(\w+)\}/', $validated['template_text'], $matches);
        $validated['variables'] = $matches[1];
        
        $template = $brand->templates()->create($validated);
        
        return response()->json($template, 201);
    }
}
```

**GeneratedContentController:**
```php
// app/Http/Controllers/Api/GeneratedContentController.php
<?php

namespace App\Http\Controllers\Api;

use App\Models\Brand;
use App\Models\GeneratedContent;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class GeneratedContentController extends Controller
{
    public function generate(Request $request)
    {
        $brand = auth()->user()->brands()->findOrFail($request->brand_id);
        
        // Get all templates and locations
        $templates = $brand->templates;
        $locations = $brand->locations()->where('active', true)->get();
        
        $jobsDispatched = 0;
        foreach ($locations as $location) {
            foreach ($templates as $template) {
                \App\Jobs\GenerateLocationContent::dispatch($location, $template);
                $jobsDispatched++;
            }
        }
        
        return response()->json(['dispatched' => $jobsDispatched]);
    }
    
    public function approve(GeneratedContent $content, Request $request)
    {
        $this->authorize('view', $content->location->brand);
        
        $content->update([
            'status' => 'approved',
            'content' => $request->content, // Allow edits
        ]);
        
        return response()->json($content);
    }
    
    public function schedule(GeneratedContent $content, Request $request)
    {
        $this->authorize('view', $content->location->brand);
        
        $content->update([
            'status' => 'scheduled',
            'scheduled_at' => $request->scheduled_at,
        ]);
        
        return response()->json($content);
    }
    
    public function publish(GeneratedContent $content)
    {
        $this->authorize('view', $content->location->brand);
        
        // TODO: Integrate with Buffer/Later API
        // For MVP: just mark as published
        $content->update(['status' => 'published']);
        
        return response()->json($content);
    }
}
```

#### 4.2 Define API Routes

```php
// routes/api.php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    BrandController,
    LocationController,
    TemplateController,
    GeneratedContentController,
};

Route::middleware('auth:sanctum')->group(function () {
    // Brands
    Route::post('/brands', [BrandController::class, 'store']);
    Route::get('/brands/{brand}', [BrandController::class, 'show']);
    Route::put('/brands/{brand}', [BrandController::class, 'update']);
    
    // Locations
    Route::post('/locations', [LocationController::class, 'store']);
    Route::post('/locations/bulk', [LocationController::class, 'bulk']);
    Route::get('/locations/{location}/content', [LocationController::class, 'generatedContent']);
    
    // Templates
    Route::post('/templates', [TemplateController::class, 'store']);
    
    // Generated Content
    Route::post('/content/generate', [GeneratedContentController::class, 'generate']);
    Route::put('/content/{content}/approve', [GeneratedContentController::class, 'approve']);
    Route::put('/content/{content}/schedule', [GeneratedContentController::class, 'schedule']);
    Route::put('/content/{content}/publish', [GeneratedContentController::class, 'publish']);
});
```

---

### Day 3-5: Frontend Dashboard (React/Vue)

#### 5.1 Dashboard Layout

```jsx
// src/pages/Dashboard.jsx
import React, { useState, useEffect } from 'react';
import api from '../api/client';

export default function Dashboard() {
  const [brands, setBrands] = useState([]);
  const [selectedBrand, setSelectedBrand] = useState(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    loadBrands();
  }, []);

  const loadBrands = async () => {
    // Load user's brands from API
  };

  return (
    <div className="dashboard">
      <nav className="sidebar">
        <h1>Hyperlocal</h1>
        <div className="brands-list">
          {brands.map(brand => (
            <div
              key={brand.id}
              className={selectedBrand?.id === brand.id ? 'active' : ''}
              onClick={() => setSelectedBrand(brand)}
            >
              {brand.name}
            </div>
          ))}
        </div>
      </nav>

      <main className="main-content">
        {selectedBrand ? (
          <BrandDashboard brand={selectedBrand} />
        ) : (
          <CreateBrand onBrandCreated={setBrands} />
        )}
      </main>
    </div>
  );
}
```

#### 5.2 Brand Dashboard Component

```jsx
// src/components/BrandDashboard.jsx
import React, { useState, useEffect } from 'react';
import { Tabs, TabContent } from '../ui/Tabs';
import api from '../api/client';

export default function BrandDashboard({ brand }) {
  const [locations, setLocations] = useState([]);
  const [templates, setTemplates] = useState([]);
  const [generatedContent, setGeneratedContent] = useState([]);
  const [generating, setGenerating] = useState(false);

  const handleGenerateContent = async () => {
    setGenerating(true);
    try {
      const res = await api.post('/content/generate', { brand_id: brand.id });
      alert(`Generated ${res.data.dispatched} pieces of content!`);
    } catch (err) {
      alert('Error generating content');
    } finally {
      setGenerating(false);
    }
  };

  return (
    <div className="brand-dashboard">
      <h2>{brand.name}</h2>

      <Tabs>
        <TabContent label="Locations">
          <LocationsManager brand={brand} />
        </TabContent>

        <TabContent label="Templates">
          <TemplatesManager brand={brand} />
        </TabContent>

        <TabContent label="Generated Content">
          <button 
            onClick={handleGenerateContent}
            disabled={generating}
          >
            {generating ? 'Generating...' : 'Generate Weekly Content'}
          </button>
          <ContentPreview brand={brand} />
        </TabContent>

        <TabContent label="Analytics">
          <Analytics brand={brand} />
        </TabContent>
      </Tabs>
    </div>
  );
}
```

#### 5.3 Locations Manager

```jsx
// src/components/LocationsManager.jsx
import React, { useState, useEffect } from 'react';
import api from '../api/client';

export default function LocationsManager({ brand }) {
  const [locations, setLocations] = useState([]);
  const [showForm, setShowForm] = useState(false);
  const [formData, setFormData] = useState({
    name: '',
    address: '',
    city: '',
    state: '',
    zip: '',
    phone: '',
  });

  useEffect(() => {
    loadLocations();
  }, [brand]);

  const loadLocations = async () => {
    // Fetch locations for brand
  };

  const handleAddLocation = async (e) => {
    e.preventDefault();
    try {
      const res = await api.post('/locations', {
        brand_id: brand.id,
        ...formData,
      });
      setLocations([...locations, res.data]);
      setFormData({ name: '', address: '', city: '', state: '', zip: '', phone: '' });
      setShowForm(false);
    } catch (err) {
      alert('Error adding location');
    }
  };

  const handleBulkImport = async (e) => {
    const file = e.target.files[0];
    const formData = new FormData();
    formData.append('csv', file);
    formData.append('brand_id', brand.id);

    try {
      const res = await api.post('/locations/bulk', formData);
      alert(`Imported ${res.data.imported} locations!`);
      loadLocations();
    } catch (err) {
      alert('Error importing locations');
    }
  };

  return (
    <div className="locations-manager">
      <h3>Manage Locations ({locations.length})</h3>

      <button onClick={() => setShowForm(!showForm)}>
        {showForm ? 'Cancel' : 'Add Location'}
      </button>

      {showForm && (
        <form onSubmit={handleAddLocation}>
          <input
            type="text"
            placeholder="Location Name"
            value={formData.name}
            onChange={(e) => setFormData({ ...formData, name: e.target.value })}
            required
          />
          <input
            type="text"
            placeholder="Address"
            value={formData.address}
            onChange={(e) => setFormData({ ...formData, address: e.target.value })}
            required
          />
          <input
            type="text"
            placeholder="City"
            value={formData.city}
            onChange={(e) => setFormData({ ...formData, city: e.target.value })}
            required
          />
          {/* ... more fields ... */}
          <button type="submit">Add Location</button>
        </form>
      )}

      <div className="bulk-import">
        <label>Or import CSV:</label>
        <input type="file" accept=".csv" onChange={handleBulkImport} />
      </div>

      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>City</th>
            <th>Phone</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {locations.map(loc => (
            <tr key={loc.id}>
              <td>{loc.name}</td>
              <td>{loc.city}</td>
              <td>{loc.phone}</td>
              <td>
                <button onClick={() => {}}>Edit</button>
                <button onClick={() => {}}>Delete</button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
```

#### 5.4 Templates Manager

```jsx
// src/components/TemplatesManager.jsx
import React, { useState, useEffect } from 'react';
import api from '../api/client';

export default function TemplatesManager({ brand }) {
  const [templates, setTemplates] = useState([]);
  const [showForm, setShowForm] = useState(false);
  const [formData, setFormData] = useState({
    name: '',
    type: 'instagram',
    template_text: '',
  });

  const handleAddTemplate = async (e) => {
    e.preventDefault();
    try {
      const res = await api.post('/templates', {
        brand_id: brand.id,
        ...formData,
      });
      setTemplates([...templates, res.data]);
      setFormData({ name: '', type: 'instagram', template_text: '' });
      setShowForm(false);
    } catch (err) {
      alert('Error adding template');
    }
  };

  return (
    <div className="templates-manager">
      <h3>Content Templates</h3>

      <button onClick={() => setShowForm(!showForm)}>
        {showForm ? 'Cancel' : 'Create Template'}
      </button>

      {showForm && (
        <form onSubmit={handleAddTemplate}>
          <input
            type="text"
            placeholder="Template Name (e.g., 'Weekly Promotion')"
            value={formData.name}
            onChange={(e) => setFormData({ ...formData, name: e.target.value })}
            required
          />

          <select
            value={formData.type}
            onChange={(e) => setFormData({ ...formData, type: e.target.value })}
          >
            <option value="instagram">Instagram</option>
            <option value="facebook">Facebook</option>
            <option value="email">Email</option>
            <option value="sms">SMS</option>
          </select>

          <textarea
            placeholder="Template text. Use {location_name}, {staff_name}, {city}, etc."
            value={formData.template_text}
            onChange={(e) => setFormData({ ...formData, template_text: e.target.value })}
            required
          />

          <p className="help-text">
            Available variables: {`{location_name}, {city}, {staff_name}, {staff_role}, {specialties}`}
          </p>

          <button type="submit">Create Template</button>
        </form>
      )}

      <div className="templates-list">
        {templates.map(template => (
          <div key={template.id} className="template-card">
            <h4>{template.name}</h4>
            <p className="type">{template.type}</p>
            <p className="preview">{template.template_text.substring(0, 100)}...</p>
          </div>
        ))}
      </div>
    </div>
  );
}
```

---

## Week 3: Testing, Refinement & Launch

### Day 1-2: Testing

#### 6.1 Unit Tests

```php
// tests/Unit/Services/ClaudeContentGeneratorTest.php
<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ClaudeContentGenerator;
use App\Models\Location;
use App\Models\Template;

class ClaudeContentGeneratorTest extends TestCase
{
    public function test_generate_content_creates_draft()
    {
        $location = Location::factory()->create();
        $template = Template::factory()->create();
        
        $generator = new ClaudeContentGenerator();
        $content = $generator->generateContent($location, $template);
        
        $this->assertIsString($content);
        $this->assertGreaterThan(0, strlen($content));
    }
    
    public function test_builds_correct_variables()
    {
        $location = Location::factory()
            ->has(Staff::factory())
            ->create();
        $template = Template::factory()->create();
        
        $generator = new ClaudeContentGenerator();
        $variables = $generator->buildVariables($location, $template);
        
        $this->assertArrayHasKey('location_name', $variables);
        $this->assertArrayHasKey('staff_name', $variables);
        $this->assertEquals($location->name, $variables['location_name']);
    }
}
```

#### 6.2 API Tests

```php
// tests/Feature/Api/BrandControllerTest.php
<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Brand;

class BrandControllerTest extends TestCase
{
    public function test_user_can_create_brand()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->postJson('/api/brands', [
                'name' => 'Test Dental',
                'brand_kit' => [],
                'content_rules' => ['tone' => 'friendly'],
            ]);
        
        $response->assertStatus(201);
        $this->assertDatabaseHas('brands', ['name' => 'Test Dental']);
    }
}
```

### Day 3-4: Refinement & Polish

#### 7.1 Add Error Handling

```php
// app/Exceptions/Handler.php
public function register()
{
    $this->reportable(function (Throwable $e) {
        // Log to Sentry
        if (env('SENTRY_LARAVEL_DSN')) {
            \Sentry\Laravel\Integration::captureUnhandledException($e);
        }
    });
}
```

#### 7.2 Add Rate Limiting

```php
// app/Http/Middleware/ThrottleRequests.php
// Prevent API abuse
Route::middleware('throttle:60,1')->group(function () {
    // API routes
});
```

#### 7.3 Create Seed Data

```php
// database/seeders/DemoDataSeeder.php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{User, Brand, Location, Template};

class DemoDataSeeder extends Seeder
{
    public function run()
    {
        $user = User::factory()->create([
            'email' => 'demo@hyperlocal.app',
            'password' => bcrypt('password'),
        ]);
        
        $brand = Brand::factory()
            ->for($user)
            ->create(['name' => 'Smile Dental']);
        
        Location::factory(5)->for($brand)->create();
        
        Template::factory()
            ->for($brand)
            ->create(['type' => 'instagram']);
    }
}
```

### Day 5: Deployment & Launch

#### 8.1 Deployment Checklist

- [ ] Migrate database on production
- [ ] Set `.env` variables (`ANTHROPIC_API_KEY`, database credentials)
- [ ] Run `php artisan config:cache`
- [ ] Run `php artisan route:cache`
- [ ] Set up Redis for queue
- [ ] Start queue worker: `php artisan queue:work`
- [ ] Set up cron job for `content:generate-weekly` command
- [ ] Enable HTTPS
- [ ] Set up monitoring (Sentry, DataDog)

#### 8.2 Launch Commands

```bash
# Production deployment
git pull origin main
composer install --no-dev
php artisan migrate --force
php artisan config:cache
php artisan route:cache

# Start queue worker (use Supervisor)
[program:hyperlocal-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/hyperlocal/artisan queue:work redis --sleep=3 --tries=3
autostart=true
autorestart=true
numprocs=4
```

#### 8.3 Monitoring & Observability

```php
// app/Providers/AppServiceProvider.php
public function boot()
{
    // Log slow queries
    DB::listen(function ($query) {
        if ($query->time > 1000) {
            Log::warning('Slow query: ' . $query->sql, $query->bindings);
        }
    });
}
```

---

## Post-MVP: Immediate Features (Week 4+)

### Phase 1: Integrations
- Buffer API (schedule to social)
- Later API (Instagram scheduling)
- Google My Business API (auto-post to GMB)

### Phase 2: Analytics
- Engagement dashboard (likes, comments, clicks per location)
- Performance comparison (which locations, templates perform best)
- Export reports (PDF/CSV)

### Phase 3: Advanced Features
- Approval workflows
- Multi-user team accounts
- Seasonal content templates
- Local event API integration

---

## Critical Implementation Notes

### Claude API Best Practices
1. **Caching:** Store generated content to avoid re-generating
2. **Error handling:** Retry failed generations with exponential backoff
3. **Cost optimization:** Use `max_tokens: 500` to limit token usage
4. **Batching:** Generate 100+ pieces in one job run (queue system)

### Database Optimization
1. Add indexes on frequently queried columns:
   ```php
   $table->index('brand_id');
   $table->index('location_id');
   $table->index(['location_id', 'status']);
   ```

2. Use eager loading to prevent N+1 queries:
   ```php
   $content = GeneratedContent::with('location', 'template')->get();
   ```

### Security Considerations
1. Authenticate all API routes with Sanctum tokens
2. Authorize users can only access their own brands
3. Validate all file uploads (CSV, images)
4. Rate limit API endpoints

### Performance Tips
1. Queue all content generation (don't wait for Claude response)
2. Cache brand/template data in Redis
3. Use database pagination (not in-memory)
4. Compress assets (CSS, JS)

---

## Success Criteria for MVP Launch

✅ Users can create brand with templates  
✅ Users can add 100+ locations (single or bulk import)  
✅ Content generates automatically via Claude API  
✅ Users can preview, approve, and edit content  
✅ All generated content is stored in database  
✅ CSV export works for manual posting  
✅ Basic analytics (post count, template usage)  
✅ No authentication issues (Sanctum working)  
✅ Queue system processes jobs without errors  
✅ Page loads in <2 seconds

---

## Local Development Quick Start

```bash
# Clone repo
git clone <repo> hyperlocal
cd hyperlocal

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate
php artisan db:seed --class=DemoDataSeeder

# Start servers
php artisan serve           # http://localhost:8000
npm run dev                 # Frontend dev server
php artisan queue:work     # Queue worker (separate terminal)

# Test generation
php artisan content:generate-weekly
```

This plan gets you from zero to MVP in 2-3 weeks. Focus on core functionality first, then expand features.