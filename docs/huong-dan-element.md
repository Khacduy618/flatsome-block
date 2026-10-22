# Hướng dẫn cài đặt element: Flatsome Block Manager 2.0

Dành cho người dựng trang bằng UX Builder. Mọi element dưới đây tự lấy màu, font và kiểu nút của site, nên bạn không cần chỉnh màu.

## Cách thêm một element

1. Mở trang (hoặc UX Block) bằng **Edit with UX Builder**.
2. Bấm dấu **+** ở chỗ muốn chèn, chọn tab **Add elements**.
3. Gõ tên element vào ô tìm (ví dụ "Before"), hoặc tìm trong nhóm **Shop** / **Media**.
4. Bấm vào element. Bảng cài đặt mở ở cột trái; canvas cập nhật ngay khi bạn đổi.
5. Bấm **Save** (hoặc **Update**) ở góc trên.

Nhóm **Advanced** có ở mọi element:

| Cài đặt | Dùng để |
|---|---|
| Class | Gắn thêm class CSS có sẵn của site (ví dụ `mt`, `readable-text`). |
| Visibility | Ẩn trên mobile / tablet / desktop, giống các element khác của Flatsome. |

**Khi element không có gì để hiện** (ví dụ lọc ra 0 sản phẩm): khách không thấy gì. Người đang đăng nhập với quyền biên tập thấy một dòng chữ mờ giải thích lý do.

---

## 1. Before / After (nhóm Media)

So sánh 2 ảnh bằng một đường kéo. Phù hợp để khoe dây da mới so với sau vài tháng đeo, hoặc trước/sau khi phục hồi.

### Ảnh

| Cài đặt | Ý nghĩa | Gợi ý |
|---|---|---|
| Trước | Ảnh hiện ở bên trái (hoặc trên). | Hai ảnh nên **cùng góc chụp**. Nếu khác tỉ lệ, chọn một **Tỉ lệ khung** cố định bên dưới. |
| Sau | Ảnh hiện ở bên phải (hoặc dưới). | |
| Kích thước ảnh | Medium · Medium Large · **Large** (mặc định) · Full | Large là đủ cho cột rộng. Chỉ chọn Full khi element rộng hết màn hình. |
| Tỉ lệ khung | **Theo ảnh** (mặc định) · 1:1 · 4:3 · 3:4 · 3:2 · 2:3 · 16:9 · 9:16 · 21:9 | Chọn khung cố định khi 2 ảnh khác tỉ lệ, hoặc muốn các khung trong Carousel bằng nhau. Ảnh tự cắt từ giữa cho vừa khung, không méo. Ảnh dây đeo dọc hợp với 3:4 hoặc 9:16. |
| Chiều rộng | Kéo 10–100 %, mặc định 100 %. Bấm biểu tượng màn hình cạnh tên để chỉnh riêng desktop / tablet / mobile. | Element hẹp hơn cột sẽ tự căn giữa. Ví dụ ảnh 9:16: desktop 40 %, mobile 100 %. |

### Cách so sánh

| Cài đặt | Ý nghĩa | Gợi ý |
|---|---|---|
| Hướng | **Ngang** (kéo trái/phải) hoặc **Dọc** (kéo lên/xuống). | Ảnh dây đeo dọc dùng Dọc sẽ dễ nhìn hơn. |
| Điều khiển | **Kéo** (mặc định) hoặc **Rê chuột**. | Rê chuột chỉ tác dụng với chuột trên máy tính. Trên điện thoại, khách vẫn kéo bằng ngón tay. |
| Vị trí ban đầu | 0–100 %, mặc định 50 %. | 30–40 % để khách thấy "Sau" nhiều hơn ngay từ đầu. |
| Tự chạy gợi ý một lần khi cuộn tới | Đường kẻ tự lướt qua một lần để khách biết là kéo được. | Không chạy nếu máy khách bật "giảm chuyển động". |
| Tay nắm | **Hiện** (nút tròn có mũi tên) hoặc **Chỉ đường kẻ**. | Nên giữ Hiện: khách dễ nhận ra là kéo được. |

### Nhãn

| Cài đặt | Ý nghĩa |
|---|---|
| Hiện nhãn | Mặc định **tắt**. Bật để hiện 2 nhãn nhỏ ở góc ảnh. |
| Nhãn trước / Nhãn sau | Chữ trên nhãn khi bật "Hiện nhãn", mặc định "Trước" / "Sau". Ví dụ: "Da mới" / "Sau 6 tháng". Khi tắt nhãn, chữ này vẫn được đọc cho người dùng trình đọc màn hình. |
| Chú thích dưới ảnh | Một dòng chữ dưới element, ví dụ "Patina sau 6 tháng đeo hằng ngày". |

### Khách dùng thế nào

- Kéo đường kẻ bằng chuột hoặc ngón tay.
- Dùng bàn phím: bấm Tab tới tay nắm, rồi phím ← → (hoặc ↑ ↓) để dịch 5 % mỗi lần, Home / End để về đầu / cuối.

---

## 2. Before / After Carousel (nhóm Media)

Đặt nhiều Before / After thành một slider.

1. Thêm **Before / After Carousel**. Preset "2 so sánh" tạo sẵn 2 khung.
2. Bấm vào từng khung con để chọn ảnh (cài đặt như mục 1).
3. Muốn thêm khung: bấm **+** bên trong carousel, chọn Before / After.

| Cài đặt | Ý nghĩa | Gợi ý |
|---|---|---|
| Số khung mỗi lượt | 1–4, chỉnh riêng cho desktop / tablet / mobile (biểu tượng màn hình cạnh tên). | Desktop 2, mobile 1. |
| Nhóm Slider | Kiểu nút, màu nút, chấm tròn, tự chạy, lặp vòng: giống Slider của Flatsome. | |

Khi khách kéo đường so sánh, slider không bị trượt theo.

---

## 3. Products (nhóm Shop)

Slider hoặc lưới sản phẩm WooCommerce, dùng đúng thẻ sản phẩm của shop (nhãn Sale, giá, nút Quick View).

### Nguồn

| Nguồn | Lấy sản phẩm nào |
|---|---|
| Mới nhất | Sản phẩm đăng gần nhất. |
| Nổi bật | Sản phẩm được đánh dấu sao (Featured). |
| Đang giảm giá | Sản phẩm có giá khuyến mãi. |
| Bán chạy | Theo số lượng đã bán. |
| Đánh giá cao | Theo điểm sao trung bình. |
| Cross-sell của sản phẩm / giỏ hàng | Trên trang sản phẩm: cross-sell của sản phẩm đó. Ở trang khác: cross-sell của hàng trong giỏ. |
| Upsell của sản phẩm | Upsell của sản phẩm đang xem. |
| Sản phẩm liên quan | Cùng danh mục / tag với sản phẩm đang xem. |
| Tự động | Lấy cross-sell; nếu không có thì upsell; nếu không có nữa thì sản phẩm liên quan. |

**Chọn tay:** chọn sản phẩm trong ô **Posts** thì element hiện đúng các sản phẩm đó theo thứ tự bạn chọn (trừ các nguồn cross-sell / upsell / liên quan / tự động).

### Lọc và sắp xếp

| Cài đặt | Ý nghĩa |
|---|---|
| Danh mục / Tag | Chỉ lấy sản phẩm thuộc danh mục / tag đã chọn. |
| Số sản phẩm | Mặc định 8, tối đa 48. |
| Bỏ qua (offset) | Bỏ N sản phẩm đầu. |
| Sắp xếp / Chiều | Mới nhất, Giá, Bán chạy, Đánh giá, Tên, Thứ tự menu, Ngẫu nhiên; tăng / giảm. |
| Giá từ / Giá đến | Khoảng giá. |
| Chỉ hàng còn | Bỏ sản phẩm hết hàng. |
| Loại trừ ID | Các ID sản phẩm không muốn hiện, cách nhau bằng dấu phẩy. |

Sản phẩm bị ẩn khỏi danh mục luôn bị bỏ. Nếu WooCommerce đang bật "ẩn hàng hết", hàng hết cũng bị bỏ.

### Bố cục

Nhóm **Layout** và **Slider** giống element Products của Flatsome: kiểu (Slider / Row / Grid…), số cột theo từng màn hình, khoảng cách, kiểu nút slider, chấm tròn, tự chạy.

Tiêu đề phía trên (ví dụ "Bán chạy") dùng element **Title** của Flatsome đặt ngay trước.

---

## 4. Product Compare (nhóm Shop)

Bảng so sánh 2–4 sản phẩm. Trên điện thoại, bảng cuộn ngang trong khung và cột tên dòng đứng yên.

| Cài đặt | Ý nghĩa |
|---|---|
| Tiêu đề bảng | Chữ đầu bảng (cũng giúp người dùng trình đọc màn hình). |
| Sản phẩm | **Chọn tay**: chọn 2–4 sản phẩm. **Đang xem + upsell**: sản phẩm đang xem so với các upsell của nó; dùng trong layout trang sản phẩm. |
| Dòng | Bật/tắt từng dòng: Ảnh, Giá, Đánh giá, Tình trạng kho, Thuộc tính, Nút mua. |
| Chỉ các thuộc tính | Tên thuộc tính muốn so, cách nhau dấu phẩy (ví dụ `Bản rộng, Loại da`). Để trống thì so mọi thuộc tính đang hiển thị. |
| Chỉ hiện dòng khác nhau | Ẩn các dòng mà mọi sản phẩm giống nhau. |

Ô nào sản phẩm không có dữ liệu sẽ hiện "—". Có dưới 2 sản phẩm hợp lệ thì bảng không hiện.

---

## 5. Product Attributes (nhóm Shop)

Bảng thông số (Bản rộng, Loại da, Màu chỉ…) của một sản phẩm, dùng đúng kiểu `handdn-product-attributes` của site.

| Cài đặt | Ý nghĩa |
|---|---|
| Sản phẩm | Để trống: sản phẩm đang xem (đặt trong trang sản phẩm hoặc Custom Product Layout). Chọn một sản phẩm: hiện thông số của sản phẩm đó ở bất kỳ trang nào. |

Chỉ hiện thuộc tính có tick "Visible on the product page" trong WooCommerce.

---

## 6. Reviews (Judge.me) (nhóm Shop)

Đặt một widget đánh giá của Judge.me vào khung trang của Flatsome. **Cần plugin Judge.me đang bật.** Đánh giá nằm trên Judge.me, element chỉ hiển thị chúng.

| Cài đặt | Ý nghĩa |
|---|---|
| Widget Judge.me | Carousel đánh giá nổi bật · Tất cả đánh giá của shop · Đánh giá của một sản phẩm · Số sao + số đánh giá · Lưới ảnh / video khách gửi · Huy hiệu shop đã xác minh. |
| Tiêu đề section | Tiêu đề phía trên, dùng kiểu Title của Flatsome. |
| Sản phẩm | Cho widget "Đánh giá của một sản phẩm" và "Số sao": để trống = sản phẩm đang xem. |
| Tiêu đề trong carousel / Link trang tất cả đánh giá | Chỉ cho "Carousel đánh giá nổi bật". |

---

## 7. Shop The Look (nhóm Shop)

Ảnh lookbook có các pin tròn; khách bấm pin nào thì khối bên phải hiện sản phẩm đó (ảnh, tên, giá, nút). Gồm hai lớp:

- **Shop The Look**: khung chung, chứa một hoặc nhiều **Look**. Có từ 2 Look thì thành slider.
- **Look**: một ảnh và tối đa 8 pin, mỗi pin gắn một sản phẩm.

### Cách dựng

1. Thêm **Shop The Look**, chọn preset *1 look* hoặc *2 looks (slider)*.
2. Bấm vào **Look** trong cây bên trái. Chọn ảnh ở *Image*, rồi chọn sản phẩm ở *Pin 1*. *Pin 2* chỉ hiện sau khi Pin 1 đã có sản phẩm, và cứ thế tới Pin 8.
3. Đặt vị trí pin theo một trong ba cách:
   - **Kéo pin ngay trên ảnh** trong canvas.
   - Khi Look đang được chọn, **bấm chỗ trống trên ảnh**: pin vừa bấm gần nhất nhảy tới đó.
   - Kéo thanh *Horizontal (X)* / *Vertical (Y)* (bước 1 %) để tinh chỉnh.
4. Thêm Look mới bằng nút **+** cạnh tab Look ở đầu canvas, hoặc *Add to Shop The Look*.

### Cài đặt của Shop The Look (áp chung cho mọi Look)

| Nhóm | Cài đặt | Ghi chú |
|---|---|---|
| General | Title, Short description, Text align, Spacing | Tiêu đề và mô tả hiện một lần, phía trên slider. *Spacing* là khoảng cách tiêu đề → mô tả → Look. |
| Look layout | Image position (Left/Right), Columns (50/50 · 60/40 · 66/33), Frame width (%), Frame height | Width / Height chỉnh riêng desktop / tablet / mobile. Có *Frame height* thì ảnh phủ kín khung và cắt từ giữa. |
| Image style | Image size, Frame ratio | *Original* = theo ảnh. |
| Pin style | Style (Dot / Number), Pulse, Tone (Light / Dark) | *Dark* dành cho ảnh nền sáng. |
| Product card | Button text | Mặc định "View product". Ảnh sản phẩm trong thẻ luôn hiện. |
| Layout · Navigation · Auto Slide | Giống hệt element **Slider** của Flatsome | Chỉ có tác dụng khi có từ 2 Look. |

### Khách dùng thế nào

- Bấm pin, hoặc Tab tới pin rồi nhấn Enter: thẻ đổi sang sản phẩm đó. Dãy chấm dưới thẻ cũng đổi được sản phẩm.
- Rê chuột lên thẻ: pin của sản phẩm đó sáng lên.
- Trên điện thoại, thẻ nằm dưới ảnh; bấm pin thì trang cuộn nhẹ tới thẻ.

### Giới hạn

- Tối đa 8 pin mỗi Look; số Look không giới hạn.
- Nút Undo của UX Builder không lùi được một cú **kéo pin**; muốn sửa thì kéo lại hoặc dùng thanh X/Y.
- Màu pin, khối nền, nút lấy theo màu của theme; không có ô chọn màu.

---

## 8. Nút "Customize this strap in 3D" (tự động)

Không phải element, không cần chèn. Nút tự hiện trên trang sản phẩm khi sản phẩm có custom field **`3D-link`** chứa một link `http(s)://`. Bật / Tắt nút ở **Block Manager › Element plugin**.

---

## 9. Pill Badge (nhóm Content)

Nhãn bo tròn có chấm màu, dùng trên tiêu đề section.

| Cài đặt | Ý nghĩa |
|---|---|
| Chữ / Liên kết / Mở | Chữ trong nhãn; có liên kết thì cả nhãn bấm được. |
| Màu nhấn | Màu chính, Thành công, Cảnh báo, Trung tính — lấy theo màu theme, không có ô chọn màu tự do. |
| Chấm tròn / Chấm nhấp nháy | Bật/tắt chấm và hiệu ứng nhấp nháy (tự tắt khi máy bật "giảm chuyển động"). |
| Cỡ / Căn | S / M / L; căn trái, giữa, phải. |

---

## 10. Bento Card (nhóm Content)

Thẻ có nhãn số thứ tự, tiêu đề, mô tả, nút link và ảnh. Đặt mỗi thẻ trong một cột; chọn **Align Vertical: Equal** cho Row để các thẻ cao bằng nhau.

| Cài đặt | Ý nghĩa |
|---|---|
| Nội dung | Nhãn số, Tiêu đề (+ thẻ H2/H3/H4/P), Mô tả, Chữ nút, Liên kết. Nút chỉ hiện khi có cả chữ nút và liên kết. |
| Ảnh › Hướng xếp | **Dọc**: ảnh tự đẩy xuống đáy thẻ. **Ngang**: ảnh nằm bên trái/phải chữ (trên điện thoại chuyển về dọc). |
| Kiểu › Nền thẻ | Viền / Nền phụ / Màu chính (chữ tự đổi trắng). |
| Bo góc, Khoảng đệm, Chiều cao tối thiểu | Để trống dùng mặc định theme; Khoảng đệm và Chiều cao chỉnh riêng được cho tablet/điện thoại. |

---

## 11. Avatar Stack (nhóm Content)

Ảnh đại diện xếp chồng + số sao + chữ đánh giá.

| Cài đặt | Ý nghĩa |
|---|---|
| Ảnh đại diện | Chọn tối đa 8 ảnh. Chưa chọn: người biên tập thấy 4 vòng tròn giữ chỗ, khách không thấy. |
| Số sao | 0–5, bước 0,1; tắt được. |
| Cỡ ảnh / Độ chồng / Căn | Cỡ 24–96px, độ chồng 0–40px. |

---

## 12. Animated Showcase & Showcase Step (nhóm Media)

Hiệu ứng showcase trình diễn quy trình chế tác hoặc tính năng sản phẩm qua hoạt ảnh trượt ảnh và gõ chữ từng ký tự:
- **Animated Showcase (Khung cha)**: Khung chứa các bước `Showcase Step`; cung cấp cài đặt chung: Tiêu đề (`Title`), Mô tả (`Description`), Thời lượng chuyển động (`Animation duration`), Độ trễ giữa các bước (`Delay between steps`), Tỉ lệ ảnh (`Image ratio`), Kích thước ảnh (`Image size`).
- **Showcase Step (Bước con)**: Một công đoạn hoặc lát cắt:
  - **Ảnh trái**: Trượt từ bên trái vào sân khấu.
  - **Nội dung chữ**: Hiệu ứng gõ chữ từng ký tự kèm hỗ trợ đầy đủ cho SEO và bộ đọc màn hình.
  - **Ảnh phải & Ảnh thứ ba**: Trượt từ dưới lên và trượt từ phải vào thay thế chữ, tạo hiệu ứng chuyển cảnh mượt mà liên tục.

## 13. Light Slider (nhóm Layout)

Slider banner / ảnh tự trượt từ phải sang trái, lặp vòng không ngừng: sau slide cuối, slide đầu trồi ra từ bên phải, không trượt ngược về đầu. Không dùng thư viện slider (Flickity). Tên cũ "Light Carousel" (`[fbm_light_carousel]`) vẫn hiển thị đúng ở nội dung đã lưu.

1. Thêm **Light Slider**, chọn preset **Banners (4 / 3 / 2)** hoặc **Single Banner Column**.
2. Hoặc chèn UX Block có sẵn `[block id="light-slider-4-columns"]` (4 cột, mỗi cột một slider 3 banner).
3. Bấm vào từng **Banner** / **Image** con để sửa. Thêm slide bằng **+** bên trong slider (nhận Banner và Image).
4. Trong canvas, slider đứng yên; cuộn ngang trong khung để tới các slide phía sau.

| Nhóm | Cài đặt | Ý nghĩa |
|---|---|---|
| Layout | Columns | Số slide hiện cùng lúc, chỉnh riêng desktop / tablet / mobile. Số lẻ (vd 1.2, 2.2) hé một phần slide kế tiếp. Để trống ở tablet / mobile thì lấy theo màn hình lớn hơn. |
| | Gap / Slides per Move / Equal Height | Khoảng cách (px, theo từng màn hình) · số slide trượt mỗi lần · ép các slide cao bằng nhau. |
| Navigation | Arrows / Always Visible | Bật nút; mặc định chỉ hiện khi rê chuột, bật Always Visible để luôn hiện. |
| | Arrow Style / Arrow & Dot Color / Bullets / Bullet Style | Giống Slider của Flatsome. |
| Auto Slide | Auto Slide / Timer / Pause on Hover / Touch | Tự trượt sau mỗi khoảng Timer (mặc định 3000 ms); dừng khi rê chuột, chạm hoặc focus. |
| | Transition Speed | Thời gian trượt (mặc định 500 ms). |

- Cần **nhiều slide hơn số slide hiện cùng lúc**, nếu không slider đứng yên và ẩn nút.
- Tự dừng khi slider ra khỏi màn hình, khi tab bị ẩn, hoặc khi máy khách bật "giảm chuyển động".
- Slide có **video nền** sẽ phát lại từ đầu khi bấm nút lùi.
- Trên mobile, chấm (bullets) chỉ để chỉ vị trí (Flatsome tắt bấm chấm ở màn hình nhỏ); khách vuốt để chuyển.

---

## Block mẫu có sẵn

Trong UX Builder: **+** › **Add Content** › **Import**. Có các bố cục HANDDN: Sản phẩm bán chạy, Danh mục theo loại da, Banner câu chuyện thương hiệu, Lợi ích sản phẩm, So sánh sản phẩm, Cam kết mua hàng, Khách hàng đánh giá, Câu hỏi thường gặp, **Frames Bento Grid Pro**, **Animated Craft Showcase Pro** (Section quy trình chế tác thủ công với hiệu ứng Animated Showcase). Hai mẫu "Thông số sản phẩm" và "Dùng kèm với sản phẩm" chỉ có khi sửa UX Block (dùng cho layout trang sản phẩm). Chữ trong mẫu là gợi ý, hãy sửa trước khi đăng.

## Bật / Ẩn / Tắt element

Ở **Block Manager** (menu admin, dưới Flatsome):

- **Ẩn**: element không còn trong Add elements, nhưng chỗ đã dùng vẫn hiển thị.
- **Tắt**: chỗ đã dùng cũng trống với khách; người biên tập thấy khung nét đứt "Đang tắt".

Nếu tìm không thấy một element trong Add elements, hãy xem nó có đang Ẩn / Tắt ở Block Manager không.
