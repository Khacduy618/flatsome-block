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
| Trước | Ảnh hiện ở bên trái (hoặc trên). | Hai ảnh nên **cùng tỷ lệ và cùng góc chụp**, nếu không đường so sánh sẽ lệch. |
| Sau | Ảnh hiện ở bên phải (hoặc dưới). | |
| Kích thước ảnh | Medium · Medium Large · **Large** (mặc định) · Full | Large là đủ cho cột rộng. Chỉ chọn Full khi element rộng hết màn hình. |

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

## 7. Nút "Customize this strap in 3D" (tự động)

Không phải element, không cần chèn. Nút tự hiện trên trang sản phẩm khi sản phẩm có custom field **`3D-link`** chứa một link `http(s)://`. Bật / Tắt nút ở **Block Manager › Element plugin**.

---

## Block mẫu có sẵn

Trong UX Builder: **+** › **Add Content** › **Import**. Có 10 bố cục HANDDN: Sản phẩm bán chạy, Danh mục theo loại da, Banner câu chuyện thương hiệu, Lợi ích sản phẩm, So sánh sản phẩm, Cam kết mua hàng, Khách hàng đánh giá, Câu hỏi thường gặp. Hai mẫu "Thông số sản phẩm" và "Dùng kèm với sản phẩm" chỉ có khi sửa UX Block (dùng cho layout trang sản phẩm). Chữ trong mẫu là gợi ý, hãy sửa trước khi đăng.

## Bật / Ẩn / Tắt element

Ở **Block Manager** (menu admin, dưới Flatsome):

- **Ẩn**: element không còn trong Add elements, nhưng chỗ đã dùng vẫn hiển thị.
- **Tắt**: chỗ đã dùng cũng trống với khách; người biên tập thấy khung nét đứt "Đang tắt".

Nếu tìm không thấy một element trong Add elements, hãy xem nó có đang Ẩn / Tắt ở Block Manager không.
