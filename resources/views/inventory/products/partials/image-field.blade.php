@php
    $currentUrl = isset($product) && $product->image ? $product->image_url : null;
    $maxMb = 5;
@endphp
<div class="product-image-field"
     :class="{ 'product-image-field--error': uploadError, 'product-image-field--drag': dragging }"
     x-data="{
         preview: @js($currentUrl),
         fileName: '',
         removed: false,
         uploadError: @js($errors->has('image') ? $errors->first('image') : null),
         dragging: false,
         maxBytes: {{ $maxMb }} * 1024 * 1024,
         pickFile() { $refs.fileInput.click(); },
         validateFile(file) {
             if (!file) return false;
             const okTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
             if (!okTypes.includes(file.type)) {
                 this.uploadError = @js(__('inventory.image_invalid_type'));
                 return false;
             }
             if (file.size > this.maxBytes) {
                 this.uploadError = @js(__('inventory.image_too_large', ['max' => $maxMb]));
                 return false;
             }
             this.uploadError = null;
             return true;
         },
         applyFile(file) {
             if (!this.validateFile(file)) return;
             this.removed = false;
             this.fileName = file.name;
             const reader = new FileReader();
             reader.onload = (ev) => { this.preview = ev.target.result; };
             reader.readAsDataURL(file);
         },
         onFile(e) {
             const file = e.target.files[0];
             if (file) this.applyFile(file);
         },
         onDrop(e) {
             e.preventDefault();
             this.dragging = false;
             const file = e.dataTransfer.files[0];
             if (!file) return;
             const dt = new DataTransfer();
             dt.items.add(file);
             $refs.fileInput.files = dt.files;
             this.applyFile(file);
         },
         clearImage() {
             this.preview = null;
             this.fileName = '';
             this.removed = true;
             this.uploadError = null;
             if ($refs.fileInput) $refs.fileInput.value = '';
         }
     }"
     @dragover.prevent="dragging = true"
     @dragleave.prevent="dragging = false"
     @drop="onDrop($event)">
    <label class="form-label d-block mb-2">Product image</label>

    <input type="hidden" name="remove_image" :value="removed ? '1' : '0'">

    <template x-if="uploadError">
        <div class="alert alert-danger py-2 px-3 tx-13 mb-2" x-text="uploadError"></div>
    </template>
    @error('image')
        <div class="alert alert-danger py-2 px-3 tx-13 mb-2">{{ $message }}</div>
    @enderror

    <div class="product-image-field__box" @click="!preview && pickFile()">
        <template x-if="preview">
            <div class="product-image-field__preview-wrap" @click.stop>
                <img :src="preview" alt="" class="product-image-field__preview">
                <div class="product-image-field__actions">
                    <button type="button" class="btn btn-sm btn-outline-primary" @click="pickFile()">
                        <i class="fe fe-upload"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" @click="clearImage()">
                        <i class="fe fe-trash-2"></i>
                    </button>
                </div>
            </div>
        </template>
        <template x-if="!preview">
            <div class="product-image-field__empty">
                <i class="fe fe-image product-image-field__icon"></i>
                <span class="product-image-field__hint">Click or drag image here</span>
                <span class="product-image-field__meta">JPG, PNG, WebP, GIF · max {{ $maxMb }} MB</span>
            </div>
        </template>
    </div>

    <input type="file"
           name="image"
           class="d-none"
           x-ref="fileInput"
           accept="image/jpeg,image/png,image/webp,image/gif"
           @change="onFile($event)">

    <div class="mt-2" x-show="fileName && !uploadError">
        <small class="text-muted" x-text="fileName"></small>
    </div>
</div>

<style>
    .product-image-field__box {
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        background: #f8fafc;
        overflow: hidden;
        min-height: 200px;
        transition: border-color 0.15s, background 0.15s;
    }
    .product-image-field--drag .product-image-field__box,
    .product-image-field__box:hover {
        border-color: #6366f1;
        background: #f5f3ff;
    }
    .product-image-field--error .product-image-field__box {
        border-color: #f87171;
        background: #fef2f2;
    }
    .product-image-field__preview-wrap {
        padding: 12px;
        text-align: center;
    }
    .product-image-field__preview {
        max-width: 100%;
        max-height: 240px;
        object-fit: contain;
        border-radius: 8px;
        display: block;
        margin: 0 auto;
    }
    .product-image-field__actions {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 0.75rem;
    }
    .product-image-field__empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 200px;
        padding: 1.5rem;
        cursor: pointer;
        text-align: center;
    }
    .product-image-field__icon {
        font-size: 2.5rem;
        color: #94a3b8;
        margin-bottom: 0.5rem;
    }
    .product-image-field__hint {
        font-size: 0.875rem;
        font-weight: 600;
        color: #334155;
    }
    .product-image-field__meta {
        font-size: 0.75rem;
        color: #64748b;
        margin-top: 0.25rem;
    }
    [dir="rtl"] .product-image-field__actions {
        flex-direction: row-reverse;
    }
</style>
