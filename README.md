# Các lệnh Git để kiểm tra trạng thái nhánh và cập nhật

Tài liệu này tổng hợp các lệnh Git quan trọng giúp bạn kiểm tra xem nhánh hiện tại đã được cập nhật mới nhất từ nhánh khác (thường là `develop`) hay chưa, đồng thời hiểu trạng thái ahead/behind giữa các nhánh.

---

## 1. Fetch (lấy về) dữ liệu mới nhất từ remote

Luôn luôn fetch trước khi kiểm tra:

```
git fetch
git fetch origin develop
```

---

## 2. Hiển thị trạng thái nhánh đang checkout và các nhánh khác

Lệnh cho biết bạn đang **ahead** hoặc **behind** remote bao nhiêu commit:

```
git branch -vv
```

---

## 3. Kiểm tra số commit khác nhau giữa nhánh hiện tại và develop

```
git rev-list --left-right --count HEAD...origin/develop
```

### Ý nghĩa:

* `0    0` → hai nhánh giống hệt nhau (bạn đang mới nhất)
* `0    X` → bạn **behind develop** X commit → chưa mới nhất
* `X    0` → bạn **ahead develop** X commit (bình thường khi làm task)
* `X    Y` → hai nhánh bị phân nhánh → cần rebase hoặc merge

---

## 4. Kiểm tra các commit mà bạn còn thiếu so với develop

Nếu lệnh này in ra commit → bạn **chưa mới nhất**:

```
git log --oneline HEAD..origin/develop
```

Nếu không in gì → bạn đã có đầy đủ commit từ develop.

---

## 5. Kiểm tra commit bạn có mà develop không có

```
git log --oneline origin/develop..HEAD
```

---

## 6. So sánh diff giữa nhánh của bạn và develop

Nếu có output → hai nhánh khác nhau:

```
git diff origin/develop
```

Nếu không ra gì → giống hoàn toàn.

---

## 7. Rebase nhánh feature với develop (cách chuẩn nhất)

```
git fetch
git rebase origin/develop
```

---

## 8. Merge develop vào feature (cách thay thế cho rebase)

```
git fetch
git merge origin/develop
```

---

## 9. Quy trình chuẩn khi gặp lỗi Divergent Branches

Khi bạn gặp lỗi:

```
fatal: Need to specify how to reconcile divergent branches.
```

Hãy làm:

1. Fetch dữ liệu mới nhất:

   ```
   git fetch
   ```
2. Rebase lại nhánh hiện tại:

   ```
   git rebase origin/develop
   ```
3. Nếu có thay đổi chưa commit → stash trước khi rebase:

   ```
   git stash
   git rebase origin/develop
   git stash pop
   ```

---

## 10. Quy trình chuẩn khi Rebase

1. Đảm bảo đang ở nhánh feature:

   ```
   git checkout feature/TASK_CODE
   ```
2. Fetch develop:

   ```
   git fetch origin develop
   ```
3. Rebase:

   ```
   git rebase origin/develop
   ```
4. Nếu có conflict:

    * Sửa conflict trong file
    * Chạy:

      ```
      git add .
      git rebase --continue
      ```
    * Hoặc hủy rebase:

      ```
      git rebase --abort
      ```

---

## 11. Hướng dẫn xử lý conflict

### Khi rebase hoặc merge gặp conflict:

* Mở file có conflict → tìm đoạn:

  ```
  <<<<<<< HEAD
  =======
  >>>>>>> other-branch
  ```
* Giữ lại phần code bạn muốn
* Xóa các ký tự conflict
* Staging file:

  ```
  git add <file>
  ```
* Tiếp tục rebase:

  ```
  git rebase --continue
  ```

---

## 12. Quy trình làm việc chuẩn với develop trong team

1. Tạo nhánh mới từ develop:

   ```
   git checkout -b feature/TASK_CODE develop
   ```
2. Làm task và commit theo chuẩn.
3. Trước khi push, luôn cập nhật lại develop:

   ```
   git fetch
   git rebase origin/develop
   ```
4. Nếu có conflict → sửa → tiếp tục.
5. Push code:

   ```
   git push -f
   ```
6. Tạo merge request vào develop.
## 13. Gộp commit (Squash commits)

Để đảm bảo mỗi task chỉ có 1 commit, trước khi tạo merge request, hãy gộp các commit lại:

1. Xem số commit bạn ahead so với develop:
   git rev-list --count HEAD...origin/develop

2. Squash:
   git rebase -i HEAD~<số_commit>

3. Trong màn hình, chuyển các commit sau `pick` thành `squash` hoặc `s`:
   pick 123abc message 1
   squash 456def message 2
   squash 789aaa message 3

4. Lưu lại, Git sẽ gom thành 1 commit.

5. Push lại:
   git push -f
