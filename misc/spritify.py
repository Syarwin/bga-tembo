#!/usr/bin/env python3
import os
import math
from PIL import Image

# Typical use case:
# - all the images to sprite are in a single folder in misc/icons/
# - script is placed in misc/
# - output image should be placed in ../img/ folder
# - output sass should be placed in ../modules/css/ folder
CLASS_PREFIX = 'icon'
FILENAME_BASIS = f"{CLASS_PREFIX}s" # default is = plural of class prefix
DEFAULT_MARGIN = 4
INPUT_DIR = f'{FILENAME_BASIS}/'
OUTPUT_ROOT_DIR = "../"
OUTPUT_SPRITE = f"img/{FILENAME_BASIS}.png"
OUTPUT_SASS = f"modules/css/_{FILENAME_BASIS}.generated.scss"
OUTPUT_PREVIEW = f"./{CLASS_PREFIX}_preview.html" # this will be outputed in current dir

# --- Packing Algorithm ---

class Node:
    """Binary tree node for rectangle packing."""
    def __init__(self, x, y, w, h):
        self.x, self.y, self.w, self.h = x, y, w, h
        self.used = False
        self.down = None
        self.right = None

    def insert(self, w, h, margin):
        if self.used:
            return self.right.insert(w, h, margin) or self.down.insert(w, h, margin)
        elif w <= self.w and h <= self.h:
            self.used = True
            self.down = Node(self.x, self.y + h + margin, self.w, self.h - h - margin)
            self.right = Node(self.x + w + margin, self.y, self.w - w - margin, h)
            return self
        else:
            return None


def pack_images(images, margin=0):
    """Pack images using a binary tree (guillotine) bin-packer."""
    total_area = sum(im.width * im.height for _, im in images)
    side = int(math.sqrt(total_area)) + margin * len(images)

    # Sort by largest first (better packing)
    images.sort(key=lambda x: max(x[1].width, x[1].height), reverse=True)

    while True:
        root = Node(0, 0, side, side)
        positions = {}
        success = True

        for name, im in images:
            node = root.insert(im.width, im.height, margin)
            if node:
                positions[name] = (node.x, node.y, im.width, im.height)
            else:
                success = False
                break

        if success:
            break
        side = int(side * 1.1)  # increase canvas size and retry

    sheet_w = max(x + w for x, y, w, h in positions.values())
    sheet_h = max(y + h for x, y, w, h in positions.values())
    return positions, sheet_w, sheet_h


# --- SASS Generation ---

def generate_sass(positions, sheet_w, sheet_h, output_path, margin=0):
    lines = []
    classLines = []
    lines.append("// Auto-generated sprite mixins")
    lines.append(f"${CLASS_PREFIX}-sprite-width: {sheet_w}px;")
    lines.append(f"${CLASS_PREFIX}-sprite-height: {sheet_h}px;")
    lines.append("")

    for name, (x, y, w, h) in positions.items():
        base = os.path.splitext(name)[0].replace(" ", "-")
        # background-size width percent so that element width == k * image_w maps correctly
        bg_size_w_pct = (sheet_w / w) * 100.0 if w != 0 else 100.0
        # background-position percents computed relative to travelable range
        x_pct = (x / (sheet_w - w)) * 100.0 if sheet_w != w else 0.0
        y_pct = (y / (sheet_h - h)) * 100.0 if sheet_h != h else 0.0

        lines.append(f"@mixin {CLASS_PREFIX}-{base} {{")
        lines.append(f"  background-image: url('{OUTPUT_SPRITE}');")
        # set width-percent background-size (auto for height keeps aspect)
        lines.append(f"  background-size: {bg_size_w_pct:.6f}% auto;")
        lines.append(f"  background-position: {x_pct:.6f}% {y_pct:.6f}%;")
        lines.append(f"  background-repeat: no-repeat;")
        lines.append(f"  aspect-ratio: {w}/{h};")
        lines.append(f"}}\n")

        classLines.append(f".{CLASS_PREFIX}-{base} {{")
        classLines.append(f"  @include {CLASS_PREFIX}-{base};")
        classLines.append(f"}}\n")

    with open(output_path, "w") as f:
        f.write("\n".join(lines))
        f.write("\n".join(classLines))
    print(f"Generated SASS file: {output_path}")


# --- Sprite Generation ---

def create_sprite(image_dir, margin=0):
    files = [f for f in os.listdir(image_dir) if f.lower().endswith((".png", ".jpg", ".jpeg"))]
    images = [(f, Image.open(os.path.join(image_dir, f)).convert("RGBA")) for f in files]

    positions, sheet_w, sheet_h = pack_images(images, margin)

    sprite = Image.new("RGBA", (sheet_w, sheet_h), (0, 0, 0, 0))
    for name, im in images:
        x, y, _, _ = positions[name]
        sprite.paste(im, (x, y))

    return sprite, positions, sheet_w, sheet_h


def save_optimized_png(image: Image.Image, path: str, remove_transparency=False, quantize=True):
    """
    Save a PNG with smaller size:
    - remove transparency (if requested)
    - quantize to ≤256 colors (if requested)
    - use PNG compression and optimization
    """
    img = image
    if remove_transparency:
        # Check if alpha is used
        if "A" in img.getbands():
            # Flatten transparency to white background
            bg = Image.new("RGB", img.size, (255, 255, 255))
            bg.paste(img, mask=img.split()[-1])
            img = bg
        else:
            img = img.convert("RGB")
    if quantize:
        img = img.convert("P", palette=Image.ADAPTIVE, colors=256)

    img.save(path, format="PNG", optimize=True, compress_level=9)
    print(f"Optimized PNG saved: {path} ({os.path.getsize(path)/1024:.1f} KB)")


# ----------------- HTML Preview Generation -----------------

def generate_preview_html(positions, sheet_w, sheet_h, sprite_filename, output_path):
    """Generate a standalone HTML preview of all sprites."""
    css_lines = []
    html_lines = []
    html_lines.append("<!DOCTYPE html>")
    html_lines.append("<html lang='en'>")
    html_lines.append("<head>")
    html_lines.append("<meta charset='UTF-8'>")
    html_lines.append("<meta name='viewport' content='width=device-width, initial-scale=1.0'>")
    html_lines.append("<title>Sprite Preview</title>")
    html_lines.append("<style>")
    html_lines.append("""
    body {
      font-family: sans-serif;
      background: #f8f8f8;
      color: #333;
      padding: 1rem;
    }
    h1 {
      text-align: center;
      margin-bottom: 1rem;
    }
    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
      gap: 1rem;
    }
    .tile {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      background: white;
      border-radius: 0.5rem;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      padding: 0.5rem;
    }
    .icon {
      width: 100%;
      max-width: 100px;
      background-repeat: no-repeat;
    }
    .name {
      margin-top: 0.5rem;
      font-size: 0.8rem;
      text-align: center;
      word-break: break-all;
    }
    """)

    content_lines = []
    for name, (x, y, w, h) in positions.items():
        base = os.path.splitext(name)[0].replace(" ", "-")
        bg_size_w_pct = (sheet_w / w) * 100.0 if w != 0 else 100.0
        x_pct = (x / (sheet_w - w)) * 100.0 if sheet_w != w else 0.0
        y_pct = (y / (sheet_h - h)) * 100.0 if sheet_h != h else 0.0

        html_lines.append(f".sprite-{base} {{")
        html_lines.append(f"  background-image: url('{sprite_filename}');")
        html_lines.append(f"  background-size: {bg_size_w_pct:.6f}% auto;")
        html_lines.append(f"  background-position: {x_pct:.6f}% {y_pct:.6f}%;")
        html_lines.append(f"  aspect-ratio: {w}/{h};")
        html_lines.append(f"  background-repeat: no-repeat;")
        html_lines.append("}")

        content_lines.append(f"<div class='tile'><div class='icon sprite-{base}'></div><div class='name'>{base}</div></div>")

    html_lines.append("</style>")
    html_lines.append("</head>")
    html_lines.append("<body>")
    html_lines.append("<h1>Sprite Preview</h1>")
    html_lines.append("<div class='grid'>")
    html_lines += content_lines[-len(positions):]
    html_lines.append("</div>")
    html_lines.append("</body></html>")

    with open(output_path, "w") as f:
        f.write("\n".join(html_lines))

    print(f"Generated preview HTML: {output_path}")


# --- CLI ---

def main():
    import argparse
    parser = argparse.ArgumentParser(description="Generate optimized sprite sheet and SASS mixins from images")
    parser.add_argument("--image_dir", default=INPUT_DIR, help="Directory containing the source images")
    parser.add_argument("--output-dir", default=OUTPUT_ROOT_DIR, help="Output root directory")
    parser.add_argument("--output-sprite", default=OUTPUT_SPRITE, help="Filename of sprite")
    parser.add_argument("--output-sass", default=OUTPUT_SASS, help="Filename of sass sprite")
    parser.add_argument("--output-html", default=OUTPUT_PREVIEW, help="Filename of html preview")
    parser.add_argument("--margin", type=int, default=DEFAULT_MARGIN, help="Margin (in pixels) between images")
    args = parser.parse_args()

    sprite, positions, w, h = create_sprite(args.image_dir, args.margin)
    sprite_path = os.path.join(args.output_dir, args.output_sprite)    
    save_optimized_png(sprite, sprite_path, quantize=True)
    print(f"Generated sprite: {sprite_path} ({w}x{h})")

    sass_path = os.path.join(args.output_dir, args.output_sass)
    generate_sass(positions, w, h, sass_path, args.margin)

    html_path = args.output_html
    generate_preview_html(positions, w, h, sprite_path, html_path)

if __name__ == "__main__":
    main()

