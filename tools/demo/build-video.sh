#!/usr/bin/env bash
# Build the GitHub Pages demo from the reviewed local screenshots.
set -euo pipefail

if [[ $# -ne 2 ]]; then
  echo "Usage: $0 FRAME_DIRECTORY OUTPUT_DIRECTORY" >&2
  exit 64
fi

frame_directory=$1
output_directory=$2
script_directory=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
captions_file="$script_directory/captions.txt"
font_file=/System/Library/Fonts/Supplemental/Arial.ttf

frames=(
  01-opening.png
  02-home-trust.png
  03-catalog.png
  04-product.png
  05-mobile-sticky.png
  06-cart-success.png
  07-cart.png
  08-upsell.png
  09-downsell.png
  10-downsell-accepted.png
  11-checkout-contact.png
  12-checkout-payment.png
  13-order-confirmed.png
  14-post-purchase.png
  15-post-purchase-detail.png
  16-toolkit-valid.png
  17-toolkit-warning.png
  18-end-card-source.png
)

durations=(3 5 6 7 3 5 3 4 4 4 3 4 3 5 5 4 4 5)

for dependency in ffmpeg magick awk; do
  command -v "$dependency" >/dev/null 2>&1 || {
    echo "Missing required command: $dependency" >&2
    exit 69
  }
done

[[ -d "$frame_directory" ]] || {
  echo "Frame directory does not exist: $frame_directory" >&2
  exit 66
}
[[ -r "$captions_file" ]] || {
  echo "Caption file does not exist: $captions_file" >&2
  exit 66
}
[[ -r "$font_file" ]] || {
  echo "Caption font does not exist: $font_file" >&2
  exit 66
}

caption_count=$(awk 'NF { count += 1 } END { print count + 0 }' "$captions_file")
[[ "$caption_count" -eq "${#frames[@]}" ]] || {
  echo "Expected ${#frames[@]} caption lines, found $caption_count." >&2
  exit 65
}

for index in "${!frames[@]}"; do
  frame=${frames[$index]}
  [[ -s "$frame_directory/$frame" ]] || {
    echo "Missing or empty frame: $frame_directory/$frame" >&2
    exit 66
  }

  caption_line=$(sed -n "$((index + 1))p" "$captions_file")
  caption_frame=${caption_line%%|*}
  caption_text=${caption_line#*|}
  [[ "$caption_frame" == "$frame" && "$caption_text" != "$caption_line" && -n "$caption_text" ]] || {
    echo "Invalid caption line $((index + 1)) for $frame." >&2
    exit 65
  }
done

mkdir -p "$output_directory"
temporary_directory=$(mktemp -d "${TMPDIR:-/tmp}/purple-cro-demo-video.XXXXXX")
cleanup() {
  rm -rf "$temporary_directory"
}
trap cleanup EXIT HUP INT TERM

concat_list="$temporary_directory/segments.txt"
: > "$concat_list"

for index in "${!frames[@]}"; do
  frame=${frames[$index]}
  duration=${durations[$index]}
  frame_count=$((duration * 30))
  fade_out_start=$((duration - 1))
  caption_line=$(sed -n "$((index + 1))p" "$captions_file")
  caption_file="$temporary_directory/caption-$index.txt"
  caption_text_image="$temporary_directory/caption-$index-text.png"
  caption_layer="$temporary_directory/caption-$index-layer.png"
  segment_file="$temporary_directory/segment-$index.ts"
  printf '%s\n' "${caption_line#*|}" > "$caption_file"

  magick -size 1500x100 -background none -fill white -font "$font_file" -pointsize 38 \
    -gravity center "caption:$(< "$caption_file")" "$caption_text_image"
  magick -size 1920x1080 xc:none "$caption_text_image" -gravity south -geometry +0+20 \
    -compose over -composite "$caption_layer"

  filter="[0:v]scale=1920:930:force_original_aspect_ratio=decrease,pad=1920:930:(ow-iw)/2:(oh-ih):color=0xf7f1e8,pad=1920:1080:0:0:color=0xf7f1e8,zoompan=z='min(zoom+0.00045,1.035)':x='iw/2-(iw/zoom/2)':y='ih/2-(ih/zoom/2)':d=$frame_count:s=1920x1080:fps=30,drawbox=x=100:y=940:w=1720:h=140:color=0x32135f@0.96:t=fill[base];[base][1:v]overlay=0:0,fade=t=in:st=0:d=0.35,fade=t=out:st=$fade_out_start:d=0.55[video]"

  ffmpeg -hide_banner -loglevel error -y -loop 1 -i "$frame_directory/$frame" -loop 1 -i "$caption_layer" \
    -filter_complex "$filter" -map '[video]' -frames:v "$frame_count" -an -c:v libx264 -preset slow \
    -crf 24 -pix_fmt yuv420p -r 30 -f mpegts "$segment_file"
  printf "file '%s'\n" "$segment_file" >> "$concat_list"
done

ffmpeg -hide_banner -loglevel error -y -f concat -safe 0 -i "$concat_list" \
  -map 0:v:0 -an -c:v libx264 -preset slow -b:v 1700k -maxrate 1700k -bufsize 3400k \
  -pix_fmt yuv420p -movflags +faststart "$temporary_directory/purple-cro-demo.mp4"

magick "$frame_directory/04-product.png" -resize '1920x1080>' -gravity center -background '#f7f1e8' \
  -extent 1920x1080 -quality 88 "$temporary_directory/purple-cro-demo-poster.webp"

mv -f "$temporary_directory/purple-cro-demo.mp4" "$output_directory/purple-cro-demo.mp4"
mv -f "$temporary_directory/purple-cro-demo-poster.webp" "$output_directory/purple-cro-demo-poster.webp"
