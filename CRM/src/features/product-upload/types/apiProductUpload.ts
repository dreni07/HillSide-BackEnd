/**
 * Forma e përgjigjes së Laravel për `extracted_product_items` (JSON).
 */

export interface ExtractedProductItemDto {
  id: number;
  business_id: number;
  user_id: number | null;
  title: string | null;
  description: string | null;
  price: string | null;
  sku: string | null;
  category: string | null;
  extracted_text: string | null;
  source_type: string;
  metadata: Record<string, unknown> | null;
  file_disk: string | null;
  file_path: string | null;
  created_at: string;
  updated_at: string;
}

export interface ProductUploadItemsData {
  items: ExtractedProductItemDto[];
}
