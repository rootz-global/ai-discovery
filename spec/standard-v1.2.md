# AI Discovery Standard v1.2 - DRAFT

## Major Changes from v1.1

### URL Structure: All Under `/.well-known/ai/`

**Breaking Change**: All endpoints now live under the `/.well-known/ai/` hierarchy.

**Old (v1.1)**:
```
/.well-known/ai          → discovery
/ai/knowledge.json       → knowledge
/ai/feed.json           → feed
```

**New (v1.2)**:
```
/.well-known/ai                  → discovery (root)
/.well-known/ai/knowledge        → knowledge
/.well-known/ai/feed            → feed
/.well-known/ai/content          → all content
/.well-known/ai/content/pages    → segmented: pages only
/.well-known/ai/content/posts    → segmented: posts only
/.well-known/ai/content/media    → segmented: media only
/.well-known/ai/archive          → archive
```

**Rationale**:
1. ✅ Everything under standard `/.well-known/` namespace (RFC 8615)
2. ✅ No redundant `.json` extensions (entire tree is JSON)
3. ✅ Hierarchical and RESTful
4. ✅ Natural segmentation for large sites
5. ✅ Easier to implement access controls per path

## Addition: Content Tier (Tier 4)

### 8. Content Endpoint

The content endpoint (`/.well-known/ai/content`) provides a complete, machine-readable representation of a website's published content. This enables AI agents to access the actual substance of a site without HTML parsing.

#### 8.1 Purpose

- **Verified Content**: Provide cryptographically verifiable access to original content
- **Structured Data**: Present content in consistent, machine-readable format
- **Complete Picture**: Include text, images, metadata in a single source of truth
- **No Scraping**: Eliminate need for HTML parsing and inference

#### 8.2 Endpoint Specification

**URL**: `/.well-known/ai/content`

**Segmented URLs** (for large sites):
- `/.well-known/ai/content/pages` - Pages only
- `/.well-known/ai/content/posts` - Blog posts only
- `/.well-known/ai/content/media` - Media library only
- `/.well-known/ai/content/{type}` - Custom post type (e.g., `portfolios`, `products`)

**Method**: GET

**Content-Type**: `application/json`

**CORS**: Should include `Access-Control-Allow-Origin: *` for AI agent access

#### 8.3 Response Structure

```json
{
  "specVersion": "1.2.0",
  "standard": "ai-content",
  "generated": "2026-02-20T10:30:00-06:00",
  "organization": {
    "name": "Organization Name",
    "domain": "example.com"
  },
  "content": {
    "pages": [],
    "posts": [],
    "custom": [],
    "media": []
  },
  "_signature": {
    "digitalName": "0x...",
    "network": "polygon",
    "contentHash": "sha256:...",
    "signedAt": "2026-02-20T10:30:00-06:00"
  }
}
```

#### 8.4 Content Types

##### Pages

Static website pages (About, Services, etc.):

```json
{
  "id": "123",
  "title": "About Us",
  "slug": "about",
  "url": "https://example.com/about",
  "published": "2025-01-15T00:00:00Z",
  "modified": "2026-01-10T00:00:00Z",
  "assertionType": "factual",
  "excerpt": "We are a company that...",
  "content": "<p>Full HTML content...</p>",
  "contentRaw": "Full raw content...",
  "wordCount": 450,
  "featuredImage": "https://example.com/wp-content/uploads/about.jpg"
}
```

##### Posts

Blog posts, articles, news:

```json
{
  "id": "456",
  "title": "Understanding Portrait Lighting",
  "slug": "understanding-portrait-lighting",
  "url": "https://example.com/blog/portrait-lighting",
  "published": "2026-02-01T00:00:00Z",
  "modified": "2026-02-15T00:00:00Z",
  "assertionType": "editorial",
  "category": "Photography Tips",
  "tags": ["lighting", "portraits", "tutorial"],
  "excerpt": "Learn the fundamentals...",
  "content": "<p>Full HTML content...</p>",
  "contentRaw": "Full raw content...",
  "wordCount": 1200,
  "featuredImage": "https://example.com/images/lighting.jpg"
}
```

##### Custom Post Types

Portfolios, galleries, products, testimonials:

```json
{
  "type": "portfolio",
  "label": "Portfolios",
  "items": [
    {
      "id": "789",
      "title": "Car Girl Series",
      "slug": "car-girl-series",
      "url": "https://example.com/portfolio/car-girl",
      "published": "2025-06-01T00:00:00Z",
      "modified": "2026-01-15T00:00:00Z",
      "assertionType": "creative-work",
      "excerpt": "A series exploring...",
      "featuredImage": "https://example.com/images/car-girl-featured.jpg",
      "images": [
        {
          "id": "890",
          "url": "https://example.com/images/car-girl-1.jpg",
          "title": "Car Girl #1: Red Mustang",
          "caption": "Subject with vintage 1967 Mustang",
          "description": "Shot on location in Austin, TX",
          "alt": "Woman leaning against red 1967 Mustang",
          "width": 3000,
          "height": 2000,
          "mime": "image/jpeg",
          "exif": {
            "camera": "Canon EOS R5",
            "focalLength": "85mm",
            "aperture": "f/1.8",
            "shutterSpeed": "1/250",
            "iso": "400"
          }
        }
      ]
    }
  ]
}
```

##### Media

Site-wide media library:

```json
{
  "id": "991",
  "url": "https://example.com/images/product-hero.jpg",
  "title": "Product Hero Image",
  "caption": "Our flagship product",
  "description": "High-resolution product photography",
  "alt": "Product on white background",
  "uploaded": "2025-12-01T00:00:00Z",
  "width": 4000,
  "height": 3000,
  "mime": "image/jpeg",
  "exif": {
    "camera": "Nikon Z9",
    "focalLength": "105mm",
    "aperture": "f/11",
    "shutterSpeed": "1/125",
    "iso": "100"
  },
  "attachedTo": {
    "id": "456",
    "title": "Product Launch Announcement",
    "url": "https://example.com/blog/product-launch"
  }
}
```

#### 8.5 Optional Fields

Implementations MAY include:

- `content`: Full HTML-rendered content
- `contentRaw`: Raw content (Markdown, plain text, etc.)
- `wordCount`: Word count of content
- `featuredImage`: Primary image URL
- `images`: Array of attached images
- `exif`: Camera/photo metadata
- `attachedTo`: Parent post/page relationship

#### 8.6 Assertion Types

Content should declare its assertion type:

- `factual`: Statements of fact about the organization
- `editorial`: Opinion, commentary, analysis
- `creative-work`: Art, photography, music, video
- `product`: E-commerce product descriptions
- `event`: Time-bound events
- `testimonial`: Third-party endorsements

#### 8.7 Privacy & Control

Publishers MUST:
- Only include published, publicly-accessible content
- Respect robots.txt and meta robots directives
- Allow selective inclusion/exclusion of content types
- Provide opt-out mechanisms for specific posts/pages

Publishers SHOULD:
- Include `noai` or similar meta tags for excluded content
- Document which content is included in `/ai/content.json`
- Provide cache control headers appropriate to content update frequency

#### 8.8 Performance Considerations

For large sites:

- Implement pagination: `/ai/content.json?page=2`
- Provide filtering: `/ai/content.json?type=post&category=news`
- Cache aggressively (24+ hours recommended)
- Consider excerpt-only mode vs full-text mode
- Set reasonable limits (e.g., 50 posts, 100 images)

#### 8.9 Capability Declaration

Sites with content endpoints MUST declare in `/.well-known/ai`:

```json
{
  "capabilities": {
    "knowledge": {
      "available": true,
      "url": "/.well-known/ai/knowledge"
    },
    "feed": {
      "available": true,
      "url": "/.well-known/ai/feed"
    },
    "content": {
      "available": true,
      "url": "/.well-known/ai/content",
      "auth": "none",
      "rateLimit": "100/hour",
      "segments": [
        "/.well-known/ai/content/pages",
        "/.well-known/ai/content/posts",
        "/.well-known/ai/content/media"
      ],
      "includes": {
        "pages": true,
        "posts": true,
        "customTypes": true,
        "media": true,
        "fullText": false
      }
    }
  }
}
```

#### 8.10 Verification

Content endpoints SHOULD include signature blocks:

```json
{
  "_signature": {
    "digitalName": "0xAddress",
    "network": "polygon",
    "contentHash": "sha256:abc123...",
    "signedAt": "2026-02-20T10:30:00Z",
    "method": "wordpress-plugin-v1"
  }
}
```

This allows AI agents to:
1. Verify content hasn't been tampered with
2. Prove content came from the claimed digital identity
3. Establish chain of custody for quoted material

#### 8.11 Use Cases

**Photography Portfolios**: "List all Car Girl paintings" → Direct answer from structured data

**E-commerce**: "What products do you sell under $50?" → Query structured product data

**News Sites**: "Quote the exact wording from your article about X" → Verified quote with content hash

**Research**: "Find all articles mentioning climate change" → Search structured content, not HTML

## Updated Grading Rubric

With content endpoint addition:

### Tier 1: Discovery (60 points)
- `/.well-known/ai` with org data, concepts, contact

### Tier 2: Extended Knowledge (25 points)
- `/ai/knowledge.json` (10 pts)
- `/ai/feed.json` (10 pts)
- Both enabled (5 pt bonus)

### Tier 3: Verification (10 points)
- Digital Name (5 pts)
- Signature block (3 pts)
- Identity contract (2 pts)

### Tier 4: Complete Content (25 points) **NEW**
- `/ai/content.json` enabled (10 pts)
- Includes pages/posts (5 pts)
- Includes media with metadata (5 pts)
- Includes custom post types (5 pts)

**Total: 120 points**

**Grading Scale:**
- 108-120 (90%+): A - AI-Optimized
- 96-107 (80-89%): B - AI-Ready
- 72-95 (60-79%): C - AI-Accessible
- 48-71 (40-59%): D - Partial
- 0-47 (<40%): F - AI-Invisible

## Implementation Status

- ✅ WordPress Plugin v1.1.0 (full implementation)
- ⏳ Spec v1.2 (this draft)
- ⏳ Scanner update (pending)
- ⏳ Reference implementations for other platforms

## Questions for Review

1. Should `fullText` inclusion be default or opt-in?
2. What's reasonable pagination size? (50 items? 100?)
3. Should we define standard query parameters?
4. How to handle very large media libraries? (1000+ images)
5. Should videos be included in media, or separate?

## Next Steps

1. Review and finalize spec language
2. Add to rootz.global/ai/standard-v1.2.md
3. Update scanner to test for content endpoint
4. Create example outputs for different site types
5. Document best practices for large sites
