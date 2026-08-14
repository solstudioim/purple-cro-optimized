#!/usr/bin/env bash
# Build the reviewed frames twice and reject any byte or decoded-frame drift.
set -euo pipefail

if [[ $# -ne 1 ]]; then
  echo "Usage: $0 FRAME_DIRECTORY" >&2
  exit 64
fi

frame_directory=$1
script_directory=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
temporary_directory=$(mktemp -d "${TMPDIR:-/tmp}/purple-cro-demo-reproducibility.XXXXXX")
cleanup() {
  rm -rf "$temporary_directory"
}
trap cleanup EXIT HUP INT TERM

mkdir "$temporary_directory/one" "$temporary_directory/two"
"$script_directory/build-video.sh" "$frame_directory" "$temporary_directory/one"
"$script_directory/build-video.sh" "$frame_directory" "$temporary_directory/two"

sha256() {
  shasum -a 256 "$1" | awk '{ print $1 }'
}

decoded_sha256() {
  ffmpeg -hide_banner -loglevel error -threads 1 -fflags +bitexact -bitexact -i "$1" \
    -map 0:v:0 -f framemd5 - | shasum -a 256 | awk '{ print $1 }'
}

first_video_hash=$(sha256 "$temporary_directory/one/purple-cro-demo.mp4")
second_video_hash=$(sha256 "$temporary_directory/two/purple-cro-demo.mp4")
[[ "$first_video_hash" == "$second_video_hash" ]] || {
  echo "MP4 hashes differ: $first_video_hash != $second_video_hash" >&2
  exit 1
}

first_frame_hash=$(decoded_sha256 "$temporary_directory/one/purple-cro-demo.mp4")
second_frame_hash=$(decoded_sha256 "$temporary_directory/two/purple-cro-demo.mp4")
[[ "$first_frame_hash" == "$second_frame_hash" ]] || {
  echo "Decoded-frame hashes differ: $first_frame_hash != $second_frame_hash" >&2
  exit 1
}

first_poster_hash=$(sha256 "$temporary_directory/one/purple-cro-demo-poster.webp")
second_poster_hash=$(sha256 "$temporary_directory/two/purple-cro-demo-poster.webp")
[[ "$first_poster_hash" == "$second_poster_hash" ]] || {
  echo "Poster hashes differ: $first_poster_hash != $second_poster_hash" >&2
  exit 1
}

printf 'MP4 SHA-256: %s\nDecoded-frame SHA-256: %s\nPoster SHA-256: %s\n' \
  "$first_video_hash" "$first_frame_hash" "$first_poster_hash"
